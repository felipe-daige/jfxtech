<?php

namespace App\Http\Controllers;

use App\Enums\PedidoStatus;
use App\Models\Cupom;
use App\Models\CupomUso;
use App\Models\Endereco;
use App\Models\Pagamento;
use App\Models\Pedido;
use App\Services\CheckoutOrderService;
use App\Services\MercadoPagoService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MercadoPagoCheckoutController extends Controller
{
    public function __construct(
        protected MercadoPagoService $mercadoPagoService,
        protected CheckoutOrderService $checkoutOrderService,
    ) {}

    public function prepare(Request $request)
    {
        if ($request->filled('payer_document')) {
            $request->merge([
                'payer_document' => preg_replace('/\D/', '', (string) $request->input('payer_document')),
            ]);
        }

        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'payer_document' => 'nullable|string|size:11',
            'cep' => 'required|string|max:9',
            'rua' => 'required|string|max:255',
            'numero' => 'required|string|max:10',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|size:2',
            'pais' => 'nullable|string|size:2',
            'frete_tipo' => 'required|in:pac,sedex,retirada,gratis',
        ]);

        $pedido = $this->checkoutOrderService->resolveActiveOrder($request, ['itens.produto', 'endereco'], [PedidoStatus::CARRINHO, PedidoStatus::PENDENTE]);

        if (! $pedido || $pedido->itens->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Seu carrinho está vazio.',
            ], 400);
        }

        $frete = $this->resolveFrete(
            $pedido,
            preg_replace('/\D/', '', $validated['cep']),
            $validated['frete_tipo']
        );

        if (! $frete) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível calcular o frete selecionado.',
            ], 422);
        }

        // Resolve customer info: request → pedido → authenticated user
        $user = Auth::user();
        $customerName = $validated['customer_name'] ?? $pedido->customer_name ?? $user?->name ?? '';
        $customerEmail = $validated['customer_email'] ?? $pedido->customer_email ?? $user?->email ?? '';
        $customerPhone = $validated['customer_phone'] ?? $pedido->customer_phone ?? $user?->phone ?? '';
        $payerDocument = $validated['payer_document'] ?? $user?->cpf;

        $subtotal = $pedido->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);
        $desconto = (float) ($pedido->valor_desconto ?? 0);
        $valorTotal = round(max(0, $subtotal - $desconto) + (float) $frete['valor'], 2);

        DB::transaction(function () use ($request, $validated, $pedido, $frete, $valorTotal, $customerName, $customerEmail, $customerPhone): void {
            $endereco = $this->persistEndereco($pedido, $validated);

            $pedido->update([
                'endereco_id' => $endereco->id,
                'status' => PedidoStatus::PENDENTE,
                'valor_total' => $valorTotal,
                'frete_tipo' => $frete['tipo'],
                'frete_valor' => $frete['valor'],
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'checkout_mode' => Auth::check() ? 'authenticated' : 'guest',
            ]);

            $this->checkoutOrderService->rememberGuestOrder($request, $pedido);
        });

        $this->captureAuthenticatedUserCpf($validated['payer_document'] ?? null);

        return response()->json([
            'success' => true,
            'checkout' => [
                'pedido_id' => $pedido->id,
                'public_key' => config('services.mercadopago.public_key'),
                'amount' => $valorTotal,
                'subtotal' => round($subtotal, 2),
                'desconto' => round($desconto, 2),
                'frete' => $frete,
                'payer' => [
                    'email' => $customerEmail,
                    'first_name' => $this->firstName($customerName),
                    'last_name' => $this->lastName($customerName),
                    'entityType' => 'individual',
                    'identification' => $payerDocument ? [
                        'type' => 'CPF',
                        'number' => $payerDocument,
                    ] : null,
                ],
                'customer' => [
                    'name' => $customerName,
                    'email' => $customerEmail,
                    'phone' => $customerPhone,
                ],
                'urls' => [
                    'pay' => route('site.checkout.mercadopago.pay'),
                    'status' => route('site.checkout.mercadopago.status', $pedido),
                    'orders' => $this->checkoutOrderService->orderUrl($pedido),
                ],
            ],
        ]);
    }

    public function pay(Request $request)
    {
        $normalizedPaymentMethodId = $this->normalizePaymentMethodId(
            $request->input('payment_method_id')
                ?? $request->input('paymentMethodId')
                ?? $request->input('selectedPaymentMethod')
                ?? $request->input('selected_payment_method')
                ?? data_get($request->input('formData'), 'payment_method_id')
                ?? data_get($request->input('formData'), 'paymentMethodId')
                ?? data_get($request->input('formData'), 'selectedPaymentMethod')
        );
        $normalizedPayerEmail = $request->input('payer.email')
            ?? data_get($request->input('formData'), 'payer.email')
            ?? data_get($request->input('formData'), 'email')
            ?? Auth::user()?->email;
        $normalizedIdentificationType = $request->input('payer.identification.type')
            ?? $request->input('payer.identificationType')
            ?? data_get($request->input('formData'), 'payer.identification.type');
        $normalizedIdentificationNumber = $request->input('payer.identification.number')
            ?? $request->input('payer.identificationNumber')
            ?? $request->input('payer_document')
            ?? data_get($request->input('formData'), 'payer.identification.number');

        if ($normalizedIdentificationNumber !== null) {
            $normalizedIdentificationNumber = preg_replace('/\D/', '', (string) $normalizedIdentificationNumber);
        }

        if ($normalizedIdentificationType === null && $normalizedIdentificationNumber !== null && $normalizedIdentificationNumber !== '') {
            $normalizedIdentificationType = 'CPF';
        }

        if ($normalizedPaymentMethodId !== null || $normalizedPayerEmail !== null || $normalizedIdentificationType !== null || $normalizedIdentificationNumber !== null) {
            $request->merge(array_filter([
                'payment_method_id' => $normalizedPaymentMethodId,
                'payer' => array_filter([
                    ...((array) $request->input('payer', [])),
                    'email' => $normalizedPayerEmail,
                    'identification' => array_filter([
                        ...((array) data_get($request->input('payer', []), 'identification', [])),
                        'type' => $normalizedIdentificationType,
                        'number' => $normalizedIdentificationNumber !== null ? preg_replace('/\D/', '', (string) $normalizedIdentificationNumber) : null,
                    ], fn ($value) => $value !== null && $value !== ''),
                ], fn ($value) => $value !== null && $value !== ''),
            ], fn ($value) => $value !== null && $value !== []));
        }

        Log::info('mercado_pago.pay.request', [
            'user_id' => Auth::id(),
            'content_type' => $request->header('Content-Type'),
            'keys' => array_keys($request->all()),
            'selected_payment_method' => $request->input('selectedPaymentMethod') ?? $request->input('selected_payment_method'),
            'payment_method_id' => $request->input('payment_method_id'),
            'payload' => $request->all(),
            'raw_body' => $request->getContent(),
        ]);

        $validated = $request->validate([
            'pedido_id' => 'required|integer',
            'payment_method_id' => 'required|string',
            'transaction_amount' => 'required|numeric|min:0.01',
            'installments' => 'nullable|integer|min:1',
            'token' => 'nullable|string',
            'issuer_id' => 'nullable',
            'payer' => 'nullable|array',
            'payer.email' => 'nullable|email',
            'payer.identification' => 'nullable|array',
            'payer.identification.type' => 'nullable|string|in:CPF',
            'payer.identification.number' => 'nullable|string|size:11',
        ]);

        $pedido = Pedido::where('id', $validated['pedido_id'])
            ->with('pagamentos')
            ->first();

        if (! $pedido || ! $this->checkoutOrderService->canAccessOrder($request, $pedido)) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado.',
            ], 404);
        }

        if ((float) $validated['transaction_amount'] !== (float) $pedido->valor_total) {
            return response()->json([
                'success' => false,
                'message' => 'O valor do pagamento não confere com o pedido.',
            ], 422);
        }

        $couponValidationError = $this->validateAppliedCoupon($pedido);

        if ($couponValidationError !== null) {
            return response()->json([
                'success' => false,
                'message' => $couponValidationError,
            ], 422);
        }

        if (! $pedido->customer_email) {
            $pedido->forceFill([
                'customer_email' => data_get($validated, 'payer.email'),
            ])->save();
        }

        $payerDocument = $this->resolvePayerDocument($validated);

        if ($payerDocument !== null && empty(data_get($validated, 'payer.identification.number'))) {
            data_set($validated, 'payer.identification.number', $payerDocument);
        }

        if (! data_get($validated, 'payer.identification.type') && data_get($validated, 'payer.identification.number')) {
            data_set($validated, 'payer.identification.type', 'CPF');
        }

        $this->captureAuthenticatedUserCpf(data_get($validated, 'payer.identification.number'));

        if ($this->paymentMethodRequiresDocument($validated['payment_method_id'] ?? null) && empty(data_get($validated, 'payer.identification.number'))) {
            $paymentMethodLabel = $this->paymentMethodDocumentLabel($validated['payment_method_id'] ?? null);

            return response()->json([
                'success' => false,
                'message' => 'Informe um CPF válido para concluir o pagamento via '.$paymentMethodLabel.'.',
                'errors' => [
                    'payer.identification.number' => ['O CPF do pagador é obrigatório para '.$paymentMethodLabel.'.'],
                ],
            ], 422);
        }

        $payer = array_filter([
            ...($validated['payer'] ?? []),
            'email' => data_get($validated, 'payer.email') ?: $pedido->customer_email ?: Auth::user()?->email,
        ], fn ($value) => $value !== null && $value !== '');

        $payload = [
            'transaction_amount' => (float) $pedido->valor_total,
            'description' => 'Pedido #'.$pedido->id,
            'payment_method_id' => $validated['payment_method_id'],
            'installments' => (int) ($validated['installments'] ?? 1),
            'payer' => $payer,
            'external_reference' => (string) $pedido->id,
        ];

        $notificationUrl = $this->resolveNotificationUrl();
        if ($notificationUrl !== null) {
            $payload['notification_url'] = $notificationUrl;
        }

        if (! empty($validated['token'])) {
            $payload['token'] = $validated['token'];
        }

        if (! empty($validated['issuer_id'])) {
            $payload['issuer_id'] = $validated['issuer_id'];
        }

        Log::info('mercado_pago.pay.gateway_payload', [
            'pedido_id' => $pedido->id,
            'user_id' => Auth::id(),
            'payment_method_id' => $payload['payment_method_id'] ?? null,
            'notification_url' => $payload['notification_url'] ?? null,
            'payload' => $payload,
        ]);

        try {
            $this->cancelPendingPayments($pedido);
        } catch (RequestException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível cancelar o pagamento pendente anterior antes de gerar uma nova cobrança.',
                'details' => $exception->response?->json(),
            ], $exception->response?->status() ?? 500);
        }

        try {
            $gatewayResponse = $this->mercadoPagoService->createPayment($payload);
        } catch (RequestException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Mercado Pago recusou a requisição.',
                'details' => $exception->response?->json(),
            ], $exception->response?->status() ?? 500);
        }

        $pagamento = $this->persistPayment($pedido, $gatewayResponse);

        return response()->json([
            'success' => true,
            'payment' => [
                'id' => $pagamento->id,
                'pedido_id' => $pedido->id,
                'status' => $gatewayResponse['status'] ?? 'pending',
                'status_detail' => $gatewayResponse['status_detail'] ?? null,
                'instructions' => $this->extractInstructions($gatewayResponse),
                'redirect_url' => $this->checkoutOrderService->orderUrl($pedido),
            ],
        ]);
    }

    public function status(Request $request, Pedido $pedido)
    {
        if (! $this->checkoutOrderService->canAccessOrder($request, $pedido)) {
            abort(403);
        }

        $pagamento = $pedido->pagamentos()->latest('id')->first();

        return response()->json([
            'success' => true,
            'pedido' => [
                'id' => $pedido->id,
                'status' => $pedido->status,
                'valor_total' => $pedido->valor_total,
            ],
            'payment' => $pagamento ? [
                'id' => $pagamento->id,
                'status' => $pagamento->status,
                'gateway_status_detail' => $pagamento->gateway_status_detail,
                'instructions' => $this->extractInstructions($pagamento->payload ?? []),
            ] : null,
            'redirect_url' => $this->checkoutOrderService->orderUrl($pedido),
        ]);
    }

    public function webhook(Request $request)
    {
        if (! $this->webhookSignatureIsValid($request)) {
            Log::warning('mercado_pago.webhook.invalid_signature', [
                'x_signature' => $request->header('x-signature'),
                'x_request_id' => $request->header('x-request-id'),
                'query' => $request->query(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'received' => false,
                'message' => 'Assinatura do webhook inválida.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $paymentId = (string) (
            $request->query('id')
            ?? $request->input('data.id')
            ?? $request->input('id')
            ?? $request->input('resource')
        );
        $topic = (string) ($request->query('topic') ?? $request->input('type') ?? $request->input('topic'));

        if ($paymentId === '' || ! in_array($topic, ['', 'payment'], true)) {
            return response()->json(['received' => true]);
        }

        try {
            $gatewayResponse = $this->mercadoPagoService->getPayment($paymentId);
        } catch (Throwable) {
            return response()->json(['received' => true]);
        }

        $externalReference = (string) ($gatewayResponse['external_reference'] ?? '');
        $pedido = Pedido::find($externalReference);

        if (! $pedido) {
            return response()->json(['received' => true]);
        }

        $this->persistPayment($pedido, $gatewayResponse);

        return response()->json(['received' => true]);
    }

    protected function persistPayment(Pedido $pedido, array $gatewayResponse): Pagamento
    {
        $gatewayPaymentId = (string) ($gatewayResponse['id'] ?? '');

        $pagamento = Pagamento::updateOrCreate(
            [
                'pedido_id' => $pedido->id,
                'gateway' => 'mercado_pago',
                'gateway_payment_id' => $gatewayPaymentId,
            ],
            [
                'metodo' => $this->mapMetodo($gatewayResponse['payment_type_id'] ?? $gatewayResponse['payment_method_id'] ?? ''),
                'status' => $this->mapPaymentStatus($gatewayResponse['status'] ?? null),
                'valor' => (float) ($gatewayResponse['transaction_amount'] ?? $pedido->valor_total),
                'data' => $this->paidAtFor($gatewayResponse['status'] ?? null),
                'gateway_status_detail' => $gatewayResponse['status_detail'] ?? null,
                'external_reference' => (string) ($gatewayResponse['external_reference'] ?? $pedido->id),
                'payload' => $gatewayResponse,
            ]
        );

        $newStatus = $this->mapOrderStatus($gatewayResponse['status'] ?? null);
        $pedido->update(['status' => $newStatus]);

        if ($newStatus === PedidoStatus::PAGO) {
            $this->recordCouponUse($pedido);
        }

        return $pagamento;
    }

    private function validateAppliedCoupon(Pedido $pedido): ?string
    {
        $couponCode = trim((string) ($pedido->cupom_codigo ?? ''));

        if ($couponCode === '') {
            return null;
        }

        $coupon = Cupom::whereRaw('UPPER(codigo) = ?', [Str::upper($couponCode)])->first();

        if (! $coupon) {
            return 'Cupom inválido.';
        }

        if (! $coupon->ativo) {
            return 'Este cupom está inativo.';
        }

        if ($coupon->valido_ate && $coupon->valido_ate->isPast()) {
            return 'Este cupom está expirado.';
        }

        if ($coupon->isRestrictedToDifferentUser($pedido->user_id)) {
            return 'Este cupom é exclusivo para o usuário vinculado.';
        }

        $alreadyRecordedForOrder = CupomUso::where('cupom_id', $coupon->id)
            ->where('pedido_id', $pedido->id)
            ->exists();

        if (
            ! $alreadyRecordedForOrder
            && $coupon->limite_usos !== null
            && $coupon->usos_realizados >= $coupon->limite_usos
        ) {
            return 'Este cupom atingiu o limite de usos.';
        }

        return null;
    }

    private function recordCouponUse(Pedido $pedido): void
    {
        $couponCode = trim((string) ($pedido->cupom_codigo ?? ''));

        if ($couponCode === '') {
            return;
        }

        $coupon = Cupom::whereRaw('UPPER(codigo) = ?', [Str::upper($couponCode)])->first();

        if (! $coupon) {
            return;
        }

        $usage = CupomUso::firstOrCreate(
            ['cupom_id' => $coupon->id, 'pedido_id' => $pedido->id],
            ['user_id' => $pedido->user_id]
        );

        if ($usage->wasRecentlyCreated) {
            $coupon->increment('usos_realizados');
        }
    }

    protected function cancelPendingPayments(Pedido $pedido): void
    {
        $pendingPayments = $pedido->pagamentos()
            ->where('gateway', 'mercado_pago')
            ->where('status', PedidoStatus::PENDENTE)
            ->whereNotNull('gateway_payment_id')
            ->orderByDesc('id')
            ->get();

        foreach ($pendingPayments as $pagamento) {
            $gatewayPaymentId = (string) $pagamento->gateway_payment_id;

            if ($gatewayPaymentId === '') {
                continue;
            }

            $gatewayResponse = $this->mercadoPagoService->cancelPayment($gatewayPaymentId);
            $this->persistPayment($pedido, $gatewayResponse);
        }
    }

    protected function resolveFrete(Pedido $pedido, string $cep, string $tipo): ?array
    {
        if ($tipo === 'gratis') {
            if (! config('services.frete_gratis_ativo', false)) {
                return null;
            }

            $minimoFrete = (float) config('services.frete_gratis_minimo', 0);
            $subtotal = $pedido->itens->sum(fn ($item) => $item->preco * $item->quantidade);

            if ($minimoFrete > 0 && $subtotal < $minimoFrete) {
                return null;
            }

            return [
                'tipo' => 'gratis',
                'label' => 'FRETE GRÁTIS',
                'valor' => 0.00,
                'prazo' => '7-12 dias úteis',
            ];
        }

        $pesoTotal = $pedido->itens->sum(function ($item) {
            $pesoProduto = $item->produto->peso ?? 0.5;

            return $pesoProduto * $item->quantidade;
        });

        $response = app(FreteController::class)->calcularFrete(new Request([
            'cep_destino' => $cep,
            'peso_total' => $pesoTotal,
        ]));

        $data = $response->getData(true);

        if (! ($data['success'] ?? false) || ! isset($data['opcoes'][$tipo])) {
            return null;
        }

        return [
            'tipo' => $tipo,
            'label' => strtoupper($tipo),
            'valor' => (float) $data['opcoes'][$tipo]['valor'],
            'prazo' => $data['opcoes'][$tipo]['prazo'] ?? null,
        ];
    }

    protected function persistEndereco(Pedido $pedido, array $validated): Endereco
    {
        $payload = [
            'user_id' => Auth::id(),
            'cep' => $validated['cep'],
            'rua' => $validated['rua'],
            'numero' => $validated['numero'],
            'complemento' => $validated['complemento'] ?? null,
            'bairro' => $validated['bairro'],
            'cidade' => $validated['cidade'],
            'estado' => $validated['estado'],
            'pais' => $validated['pais'] ?? 'BR',
        ];

        if (Auth::check()) {
            return Endereco::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'cep' => $payload['cep'],
                    'rua' => $payload['rua'],
                    'numero' => $payload['numero'],
                    'bairro' => $payload['bairro'],
                    'cidade' => $payload['cidade'],
                    'estado' => $payload['estado'],
                ],
                [
                    'complemento' => $payload['complemento'],
                    'pais' => $payload['pais'],
                ]
            );
        }

        if ($pedido->endereco) {
            $pedido->endereco->update($payload);

            return $pedido->endereco->fresh();
        }

        return Endereco::create($payload);
    }

    protected function captureAuthenticatedUserCpf(?string $cpf): void
    {
        $authenticatedUser = Auth::user();

        if (! $authenticatedUser || ! empty($authenticatedUser->cpf) || empty($cpf)) {
            return;
        }

        try {
            $authenticatedUser->update(['cpf' => preg_replace('/\D/', '', $cpf)]);
        } catch (\Illuminate\Database\QueryException) {
            Log::info('cpf_capture.skipped', [
                'user_id' => $authenticatedUser->id,
                'reason' => 'unique_conflict',
            ]);
        }
    }

    protected function extractInstructions(array $gatewayResponse): array
    {
        $transactionData = $gatewayResponse['point_of_interaction']['transaction_data'] ?? [];

        return array_filter([
            'qr_code' => $transactionData['qr_code'] ?? null,
            'qr_code_base64' => $transactionData['qr_code_base64'] ?? null,
            'ticket_url' => $transactionData['ticket_url'] ?? ($gatewayResponse['transaction_details']['external_resource_url'] ?? null),
        ], fn ($value) => $value !== null && $value !== '');
    }

    protected function mapMetodo(?string $method): string
    {
        return match ($method) {
            'pix' => 'pix',
            'ticket', 'bolbradesco', 'pec' => 'boleto',
            default => 'cartao',
        };
    }

    protected function normalizePaymentMethodId(mixed $rawValue): ?string
    {
        if (is_array($rawValue)) {
            $rawValue = $rawValue['type']
                ?? $rawValue['id']
                ?? $rawValue['payment_method_id']
                ?? $rawValue['paymentMethodId']
                ?? null;
        }

        if ($rawValue === null || $rawValue === '') {
            return null;
        }

        $normalized = trim((string) $rawValue);

        return match ($normalized) {
            'bank_transfer', 'bankTransfer' => 'pix',
            'ticket' => 'bolbradesco',
            default => $normalized,
        };
    }

    protected function resolvePayerDocument(array $validated): ?string
    {
        $document = data_get($validated, 'payer.identification.number')
            ?: Auth::user()?->cpf;

        if (! is_string($document) || $document === '') {
            return null;
        }

        $document = preg_replace('/\D/', '', $document);

        return strlen($document) === 11 ? $document : null;
    }

    protected function paymentMethodRequiresDocument(?string $paymentMethodId): bool
    {
        return in_array($paymentMethodId, ['pix', 'bolbradesco', 'pec', 'ticket'], true);
    }

    protected function paymentMethodDocumentLabel(?string $paymentMethodId): string
    {
        return match ($paymentMethodId) {
            'pix' => 'Pix',
            'bolbradesco', 'pec', 'ticket' => 'boleto',
            default => 'este método de pagamento',
        };
    }

    protected function resolveNotificationUrl(): ?string
    {
        $configuredWebhookUrl = config('services.mercadopago.webhook_url');

        if ($this->isValidHttpsUrl($configuredWebhookUrl)) {
            return $configuredWebhookUrl;
        }

        Log::warning('mercado_pago.notification_url.skipped', [
            'candidate' => $configuredWebhookUrl,
            'reason' => $configuredWebhookUrl ? 'not_https' : 'missing_or_invalid',
        ]);

        return null;
    }

    protected function isValidHttpsUrl(mixed $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        return Str::startsWith($value, 'https://');
    }

    protected function webhookSignatureIsValid(Request $request): bool
    {
        $secret = (string) config('services.mercadopago.webhook_secret');

        if ($secret === '') {
            return true;
        }

        $xSignature = (string) $request->header('x-signature', '');
        $xRequestId = (string) $request->header('x-request-id', '');
        $dataId = $this->webhookDataIdForSignature($request);
        $signatureParts = $this->parseWebhookSignatureHeader($xSignature);
        $timestamp = $signatureParts['ts'] ?? null;
        $hash = $signatureParts['v1'] ?? null;

        if ($dataId === null || $timestamp === null || $hash === null || $xRequestId === '') {
            return false;
        }

        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', $dataId, $xRequestId, $timestamp);
        $expectedHash = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expectedHash, $hash);
    }

    protected function webhookDataIdForSignature(Request $request): ?string
    {
        $dataId = $request->query('id')
            ?? $request->query('data.id')
            ?? data_get($request->query(), 'data.id')
            ?? $request->input('data.id')
            ?? $request->input('id')
            ?? $request->input('resource');

        if ($dataId === null || $dataId === '') {
            return null;
        }

        return strtolower(trim((string) $dataId));
    }

    protected function parseWebhookSignatureHeader(?string $header): array
    {
        if (! is_string($header) || trim($header) === '') {
            return [];
        }

        $parts = [];

        foreach (explode(',', $header) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);

            if ($key === null || $value === null) {
                continue;
            }

            $parts[trim($key)] = trim($value);
        }

        return $parts;
    }

    protected function mapPaymentStatus(?string $status): string
    {
        return match ($status) {
            'approved' => PedidoStatus::PAGO,
            'rejected', 'cancelled' => 'falhou',
            default => PedidoStatus::PENDENTE,
        };
    }

    protected function mapOrderStatus(?string $status): string
    {
        return match ($status) {
            'approved' => PedidoStatus::PAGO,
            'authorized', 'in_process' => PedidoStatus::PROCESSANDO,
            'rejected', 'cancelled' => PedidoStatus::CANCELADO,
            default => PedidoStatus::PENDENTE,
        };
    }

    protected function paidAtFor(?string $status): ?string
    {
        return $status === 'approved' ? now()->toDateTimeString() : null;
    }

    protected function firstName(string $name): string
    {
        return explode(' ', trim($name))[0] ?? $name;
    }

    protected function lastName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if (count($parts) <= 1) {
            return '';
        }

        array_shift($parts);

        return implode(' ', $parts);
    }
}
