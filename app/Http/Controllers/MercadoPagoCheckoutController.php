<?php

namespace App\Http\Controllers;

use App\Models\Endereco;
use App\Models\Pagamento;
use App\Models\Pedido;
use App\Services\MercadoPagoService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class MercadoPagoCheckoutController extends Controller
{
    public function __construct(
        protected MercadoPagoService $mercadoPagoService
    ) {
    }

    public function prepare(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Você precisa estar logado para continuar.',
            ], 401);
        }

        $validated = $request->validate([
            'cep' => 'required|string|max:9',
            'rua' => 'required|string|max:255',
            'numero' => 'required|string|max:10',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|size:2',
            'pais' => 'nullable|string|size:2',
            'frete_tipo' => 'required|in:pac,sedex',
        ]);

        $pedido = Pedido::where('user_id', Auth::id())
            ->whereIn('status', ['carrinho', 'pendente'])
            ->with(['itens.produto', 'endereco'])
            ->latest('id')
            ->first();

        if (!$pedido || $pedido->itens->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Seu carrinho está vazio.',
            ], 400);
        }

        $frete = $this->resolveFrete(
            preg_replace('/\D/', '', $validated['cep']),
            $validated['frete_tipo']
        );

        if (!$frete) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível calcular o frete selecionado.',
            ], 422);
        }

        $subtotal = $pedido->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);
        $valorTotal = round($subtotal + (float) $frete['valor'], 2);

        DB::transaction(function () use ($validated, $pedido, $frete, $valorTotal): void {
            $endereco = Endereco::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'cep' => $validated['cep'],
                    'rua' => $validated['rua'],
                    'numero' => $validated['numero'],
                    'bairro' => $validated['bairro'],
                    'cidade' => $validated['cidade'],
                    'estado' => $validated['estado'],
                ],
                [
                    'complemento' => $validated['complemento'] ?? null,
                    'pais' => $validated['pais'] ?? 'BR',
                ]
            );

            $pedido->update([
                'endereco_id' => $endereco->id,
                'status' => 'pendente',
                'valor_total' => $valorTotal,
                'frete_tipo' => $frete['tipo'],
                'frete_valor' => $frete['valor'],
            ]);
        });

        return response()->json([
            'success' => true,
            'checkout' => [
                'pedido_id' => $pedido->id,
                'public_key' => config('services.mercadopago.public_key'),
                'amount' => $valorTotal,
                'subtotal' => round($subtotal, 2),
                'frete' => $frete,
                'payer' => [
                    'email' => Auth::user()->email,
                    'first_name' => $this->firstName(Auth::user()->name),
                    'last_name' => $this->lastName(Auth::user()->name),
                ],
                'customer' => [
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'phone' => Auth::user()->phone,
                ],
                'urls' => [
                    'pay' => route('site.checkout.mercadopago.pay'),
                    'status' => route('site.checkout.mercadopago.status', $pedido),
                    'orders' => route('site.pedidos.index'),
                ],
            ],
        ]);
    }

    public function pay(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Você precisa estar logado para continuar.',
            ], 401);
        }

        $validated = $request->validate([
            'pedido_id' => 'required|integer',
            'payment_method_id' => 'required|string',
            'transaction_amount' => 'required|numeric|min:0.01',
            'installments' => 'nullable|integer|min:1',
            'token' => 'nullable|string',
            'issuer_id' => 'nullable',
            'payer' => 'required|array',
            'payer.email' => 'required|email',
        ]);

        $pedido = Pedido::where('id', $validated['pedido_id'])
            ->where('user_id', Auth::id())
            ->with('pagamentos')
            ->first();

        if (!$pedido) {
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

        $payload = [
            'transaction_amount' => (float) $pedido->valor_total,
            'description' => 'Pedido #' . $pedido->id,
            'payment_method_id' => $validated['payment_method_id'],
            'installments' => (int) ($validated['installments'] ?? 1),
            'payer' => $validated['payer'],
            'external_reference' => (string) $pedido->id,
            'notification_url' => config('services.mercadopago.webhook_url') ?: route('site.checkout.mercadopago.webhook'),
        ];

        if (!empty($validated['token'])) {
            $payload['token'] = $validated['token'];
        }

        if (!empty($validated['issuer_id'])) {
            $payload['issuer_id'] = $validated['issuer_id'];
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
                'redirect_url' => route('site.pedidos.show', $pedido),
            ],
        ]);
    }

    public function status(Pedido $pedido)
    {
        if (!Auth::check() || $pedido->user_id !== Auth::id()) {
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
        ]);
    }

    public function webhook(Request $request)
    {
        $paymentId = (string) ($request->input('data.id') ?? $request->input('id'));
        $topic = (string) ($request->input('type') ?? $request->input('topic'));

        if ($paymentId === '' || !in_array($topic, ['', 'payment'], true)) {
            return response()->json(['received' => true]);
        }

        try {
            $gatewayResponse = $this->mercadoPagoService->getPayment($paymentId);
        } catch (Throwable) {
            return response()->json(['received' => true]);
        }

        $externalReference = (string) ($gatewayResponse['external_reference'] ?? '');
        $pedido = Pedido::find($externalReference);

        if (!$pedido) {
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

        $pedido->update([
            'status' => $this->mapOrderStatus($gatewayResponse['status'] ?? null),
        ]);

        return $pagamento;
    }

    protected function resolveFrete(string $cep, string $tipo): ?array
    {
        $response = app(FreteController::class)->calcularFreteCarrinho(new Request([
            'cep_destino' => $cep,
        ]));

        $data = $response->getData(true);

        if (!($data['success'] ?? false) || !isset($data['opcoes'][$tipo])) {
            return null;
        }

        return [
            'tipo' => $tipo,
            'label' => strtoupper($tipo),
            'valor' => (float) $data['opcoes'][$tipo]['valor'],
            'prazo' => $data['opcoes'][$tipo]['prazo'] ?? null,
        ];
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

    protected function mapPaymentStatus(?string $status): string
    {
        return match ($status) {
            'approved' => 'pago',
            'rejected', 'cancelled' => 'falhou',
            default => 'pendente',
        };
    }

    protected function mapOrderStatus(?string $status): string
    {
        return match ($status) {
            'approved' => 'pago',
            'authorized', 'in_process' => 'processando',
            'rejected', 'cancelled' => 'cancelado',
            default => 'pendente',
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
