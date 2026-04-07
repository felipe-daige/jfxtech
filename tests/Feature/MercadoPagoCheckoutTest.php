<?php

namespace Tests\Feature;

use App\Models\ItemPedido;
use App\Models\Pagamento;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MercadoPagoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.mercadopago.public_key' => 'TEST-PUBLIC-KEY',
            'services.mercadopago.access_token' => 'TEST-ACCESS-TOKEN',
            'services.mercadopago.webhook_url' => 'https://example.test/webhooks/mercado-pago',
            'services.mercadopago.webhook_secret' => null,
        ]);
    }

    public function test_prepare_updates_order_and_returns_checkout_payload(): void
    {
        $user = User::factory()->create([
            'phone' => '(43) 99999-9999',
        ]);

        $pedido = $this->makeCart($user, 100.00, 2);

        $response = $this->actingAs($user)->postJson(route('site.checkout.mercadopago.prepare'), [
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '(43) 99999-9999',
            'cep' => '86010-000',
            'rua' => 'Rua Teste',
            'numero' => '123',
            'complemento' => 'Sala 1',
            'bairro' => 'Centro',
            'cidade' => 'Londrina',
            'estado' => 'PR',
            'pais' => 'BR',
            'frete_tipo' => 'pac',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('checkout.pedido_id', $pedido->id)
            ->assertJsonPath('checkout.public_key', 'TEST-PUBLIC-KEY')
            ->assertJsonPath('checkout.frete.tipo', 'pac')
            ->assertJsonPath('checkout.amount', 218)
            ->assertJsonPath('checkout.payer.entityType', 'individual');

        $pedido->refresh();

        $this->assertSame('pendente', $pedido->status);
        $this->assertSame('pac', $pedido->frete_tipo);
        $this->assertEquals(18.00, (float) $pedido->frete_valor);
        $this->assertEquals(218.00, (float) $pedido->valor_total);
        $this->assertNotNull($pedido->endereco_id);
    }

    public function test_prepare_accepts_retirada_with_zero_shipping(): void
    {
        $user = User::factory()->create([
            'phone' => '(43) 99999-9999',
        ]);

        $pedido = $this->makeCart($user, 50.00, 1);

        $response = $this->actingAs($user)->postJson(route('site.checkout.mercadopago.prepare'), [
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '(43) 99999-9999',
            'cep' => '86010-000',
            'rua' => 'Rua Teste',
            'numero' => '123',
            'complemento' => null,
            'bairro' => 'Centro',
            'cidade' => 'Londrina',
            'estado' => 'PR',
            'pais' => 'BR',
            'frete_tipo' => 'retirada',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('checkout.pedido_id', $pedido->id)
            ->assertJsonPath('checkout.frete.tipo', 'retirada')
            ->assertJsonPath('checkout.frete.valor', 0)
            ->assertJsonPath('checkout.amount', 50);

        $pedido->refresh();

        $this->assertSame('pendente', $pedido->status);
        $this->assertSame('retirada', $pedido->frete_tipo);
        $this->assertEquals(0.00, (float) $pedido->frete_valor);
        $this->assertEquals(50.00, (float) $pedido->valor_total);
    }

    public function test_pay_persists_gateway_response_and_marks_order_paid(): void
    {
        $user = User::factory()->create();
        $pedido = $this->makeCart($user, 100.00, 1, 'pendente');
        $pedido->update([
            'valor_total' => 114.50,
            'frete_tipo' => 'pac',
            'frete_valor' => 14.50,
        ]);

        $this->mock(MercadoPagoService::class, function ($mock) use ($pedido): void {
            $mock->shouldReceive('createPayment')
                ->once()
                ->andReturn([
                    'id' => 123456789,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'transaction_amount' => 114.50,
                    'payment_method_id' => 'visa',
                    'payment_type_id' => 'credit_card',
                    'external_reference' => (string) $pedido->id,
                ]);
        });

        $response = $this->actingAs($user)->postJson(route('site.checkout.mercadopago.pay'), [
            'pedido_id' => $pedido->id,
            'payment_method_id' => 'visa',
            'transaction_amount' => 114.50,
            'installments' => 1,
            'token' => 'test-token',
            'issuer_id' => '123',
            'payer' => [
                'email' => $user->email,
                'identification' => [
                    'type' => 'CPF',
                    'number' => '12345678901',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment.status', 'approved')
            ->assertJsonPath('payment.pedido_id', $pedido->id);

        $pedido->refresh();

        $this->assertSame('pago', $pedido->status);
        $this->assertDatabaseHas('pagamentos', [
            'pedido_id' => $pedido->id,
            'gateway' => 'mercado_pago',
            'gateway_payment_id' => '123456789',
            'status' => 'pago',
            'metodo' => 'cartao',
        ]);
    }

    public function test_pay_includes_https_notification_url_when_configured(): void
    {
        config([
            'services.mercadopago.webhook_url' => 'https://example.test/webhooks/mercado-pago',
        ]);

        $user = User::factory()->create();
        $pedido = $this->makeCart($user, 100.00, 1, 'pendente');
        $pedido->update([
            'valor_total' => 114.50,
            'frete_tipo' => 'pac',
            'frete_valor' => 14.50,
        ]);

        $this->mock(MercadoPagoService::class, function ($mock) use ($pedido): void {
            $mock->shouldReceive('createPayment')
                ->once()
                ->with(\Mockery::on(function (array $payload): bool {
                    return ($payload['notification_url'] ?? null) === 'https://example.test/webhooks/mercado-pago';
                }))
                ->andReturn([
                    'id' => 123456789,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'transaction_amount' => 114.50,
                    'payment_method_id' => 'visa',
                    'payment_type_id' => 'credit_card',
                    'external_reference' => (string) $pedido->id,
                ]);
        });

        $this->actingAs($user)->postJson(route('site.checkout.mercadopago.pay'), [
            'pedido_id' => $pedido->id,
            'payment_method_id' => 'visa',
            'transaction_amount' => 114.50,
            'installments' => 1,
            'token' => 'test-token',
            'issuer_id' => '123',
            'payer' => [
                'email' => $user->email,
                'identification' => [
                    'type' => 'CPF',
                    'number' => '12345678901',
                ],
            ],
        ])->assertOk();
    }

    public function test_pay_omits_http_notification_url(): void
    {
        config([
            'app.url' => 'http://example.test',
            'services.mercadopago.webhook_url' => 'http://example.test/webhooks/mercado-pago',
        ]);
        URL::forceRootUrl('http://example.test');
        URL::forceScheme('http');

        $user = User::factory()->create();
        $pedido = $this->makeCart($user, 100.00, 1, 'pendente');
        $pedido->update([
            'valor_total' => 114.50,
            'frete_tipo' => 'pac',
            'frete_valor' => 14.50,
        ]);

        $this->mock(MercadoPagoService::class, function ($mock) use ($pedido): void {
            $mock->shouldReceive('createPayment')
                ->once()
                ->with(\Mockery::on(function (array $payload): bool {
                    return !array_key_exists('notification_url', $payload);
                }))
                ->andReturn([
                    'id' => 123456789,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'transaction_amount' => 114.50,
                    'payment_method_id' => 'visa',
                    'payment_type_id' => 'credit_card',
                    'external_reference' => (string) $pedido->id,
                ]);
        });

        $this->actingAs($user)->postJson(route('site.checkout.mercadopago.pay'), [
            'pedido_id' => $pedido->id,
            'payment_method_id' => 'visa',
            'transaction_amount' => 114.50,
            'installments' => 1,
            'token' => 'test-token',
            'issuer_id' => '123',
            'payer' => [
                'email' => $user->email,
                'identification' => [
                    'type' => 'CPF',
                    'number' => '12345678901',
                ],
            ],
        ])->assertOk();

        URL::forceRootUrl(null);
        URL::forceScheme(null);
    }

    public function test_pay_uses_authenticated_user_email_as_payer_fallback_for_pix(): void
    {
        $user = User::factory()->create([
            'email' => 'pix@example.com',
        ]);
        $pedido = $this->makeCart($user, 50.00, 1, 'pendente');
        $pedido->update([
            'valor_total' => 68.00,
            'frete_tipo' => 'pac',
            'frete_valor' => 18.00,
        ]);

        $this->mock(MercadoPagoService::class, function ($mock) use ($pedido): void {
            $mock->shouldReceive('createPayment')
                ->once()
                ->with(\Mockery::on(function (array $payload): bool {
                    return $payload['payment_method_id'] === 'pix'
                        && $payload['installments'] === 1
                        && data_get($payload, 'payer.email') === 'pix@example.com'
                        && data_get($payload, 'payer.identification.number') === '12345678901';
                }))
                ->andReturn([
                    'id' => 987654321,
                    'status' => 'pending',
                    'status_detail' => 'pending_waiting_transfer',
                    'transaction_amount' => 68.00,
                    'payment_method_id' => 'pix',
                    'payment_type_id' => 'pix',
                    'external_reference' => (string) $pedido->id,
                    'point_of_interaction' => [
                        'transaction_data' => [
                            'qr_code' => '000201PIX',
                        ],
                    ],
                ]);
        });

        $response = $this->actingAs($user)->postJson(route('site.checkout.mercadopago.pay'), [
            'pedido_id' => $pedido->id,
            'payment_method_id' => 'pix',
            'transaction_amount' => 68.00,
            'payer' => [
                'identification' => [
                    'type' => 'CPF',
                    'number' => '123.456.789-01',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment.status', 'pending')
            ->assertJsonPath('payment.instructions.qr_code', '000201PIX');

        $this->assertDatabaseHas('pagamentos', [
            'pedido_id' => $pedido->id,
            'gateway' => 'mercado_pago',
            'gateway_payment_id' => '987654321',
            'status' => 'pendente',
            'metodo' => 'pix',
        ]);
    }

    public function test_pay_requires_payment_method_id(): void
    {
        $user = User::factory()->create();
        $pedido = $this->makeCart($user, 100.00, 1, 'pendente');

        $response = $this->actingAs($user)->postJson(route('site.checkout.mercadopago.pay'), [
            'pedido_id' => $pedido->id,
            'transaction_amount' => 100.00,
            'payer' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method_id']);
    }

    public function test_pay_requires_payer_identification_for_pix(): void
    {
        $user = User::factory()->create();
        $pedido = $this->makeCart($user, 50.00, 1, 'pendente');
        $pedido->update([
            'valor_total' => 68.00,
            'frete_tipo' => 'pac',
            'frete_valor' => 18.00,
        ]);

        $response = $this->actingAs($user)->postJson(route('site.checkout.mercadopago.pay'), [
            'pedido_id' => $pedido->id,
            'payment_method_id' => 'pix',
            'transaction_amount' => 68.00,
            'payer' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Informe um CPF válido para concluir o pagamento via Pix.')
            ->assertJsonValidationErrors(['payer.identification.number']);
    }

    public function test_pay_maps_selected_payment_method_bank_transfer_to_pix(): void
    {
        $user = User::factory()->create([
            'email' => 'pix-mapped@example.com',
        ]);
        $pedido = $this->makeCart($user, 80.00, 1, 'pendente');
        $pedido->update([
            'valor_total' => 98.00,
            'frete_tipo' => 'pac',
            'frete_valor' => 18.00,
        ]);

        $this->mock(MercadoPagoService::class, function ($mock) use ($pedido): void {
            $mock->shouldReceive('createPayment')
                ->once()
                ->with(\Mockery::on(function (array $payload): bool {
                    return $payload['payment_method_id'] === 'pix'
                        && data_get($payload, 'payer.email') === 'pix-mapped@example.com'
                        && data_get($payload, 'payer.identification.number') === '12345678901';
                }))
                ->andReturn([
                    'id' => 222333444,
                    'status' => 'pending',
                    'status_detail' => 'pending_waiting_transfer',
                    'transaction_amount' => 98.00,
                    'payment_method_id' => 'pix',
                    'payment_type_id' => 'pix',
                    'external_reference' => (string) $pedido->id,
                ]);
        });

        $response = $this->actingAs($user)->postJson(route('site.checkout.mercadopago.pay'), [
            'pedido_id' => $pedido->id,
            'selectedPaymentMethod' => 'bank_transfer',
            'transaction_amount' => 98.00,
            'payer' => [
                'identification' => [
                    'type' => 'CPF',
                    'number' => '123.456.789-01',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment.status', 'pending');
    }

    public function test_pay_cancels_previous_pending_pix_before_creating_new_payment(): void
    {
        $user = User::factory()->create([
            'email' => 'switch-method@example.com',
        ]);
        $pedido = $this->makeCart($user, 90.00, 1, 'pendente');
        $pedido->update([
            'valor_total' => 108.00,
            'frete_tipo' => 'pac',
            'frete_valor' => 18.00,
        ]);

        Pagamento::create([
            'pedido_id' => $pedido->id,
            'metodo' => 'pix',
            'gateway' => 'mercado_pago',
            'gateway_payment_id' => '555111',
            'status' => 'pendente',
            'valor' => 108.00,
            'gateway_status_detail' => 'pending_waiting_transfer',
            'payload' => [
                'id' => 555111,
                'status' => 'pending',
                'payment_method_id' => 'pix',
                'payment_type_id' => 'pix',
                'external_reference' => (string) $pedido->id,
            ],
        ]);

        $this->mock(MercadoPagoService::class, function ($mock) use ($pedido): void {
            $mock->shouldReceive('cancelPayment')
                ->once()
                ->with('555111')
                ->andReturn([
                    'id' => 555111,
                    'status' => 'cancelled',
                    'status_detail' => 'cancelled',
                    'transaction_amount' => 108.00,
                    'payment_method_id' => 'pix',
                    'payment_type_id' => 'pix',
                    'external_reference' => (string) $pedido->id,
                ]);

            $mock->shouldReceive('createPayment')
                ->once()
                ->with(\Mockery::on(function (array $payload): bool {
                    return $payload['payment_method_id'] === 'visa'
                        && data_get($payload, 'payer.email') === 'switch-method@example.com';
                }))
                ->andReturn([
                    'id' => 999888777,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'transaction_amount' => 108.00,
                    'payment_method_id' => 'visa',
                    'payment_type_id' => 'credit_card',
                    'external_reference' => (string) $pedido->id,
                ]);
        });

        $response = $this->actingAs($user)->postJson(route('site.checkout.mercadopago.pay'), [
            'pedido_id' => $pedido->id,
            'payment_method_id' => 'visa',
            'transaction_amount' => 108.00,
            'installments' => 1,
            'token' => 'card-token',
            'payer' => [
                'email' => $user->email,
                'identification' => [
                    'type' => 'CPF',
                    'number' => '12345678901',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment.status', 'approved');

        $this->assertDatabaseHas('pagamentos', [
            'pedido_id' => $pedido->id,
            'gateway' => 'mercado_pago',
            'gateway_payment_id' => '555111',
            'status' => 'falhou',
            'gateway_status_detail' => 'cancelled',
        ]);

        $this->assertDatabaseHas('pagamentos', [
            'pedido_id' => $pedido->id,
            'gateway' => 'mercado_pago',
            'gateway_payment_id' => '999888777',
            'status' => 'pago',
            'metodo' => 'cartao',
        ]);
    }

    public function test_webhook_updates_existing_payment_status(): void
    {
        $user = User::factory()->create();
        $pedido = $this->makeCart($user, 90.00, 1, 'pendente');
        $pedido->update([
            'valor_total' => 104.50,
            'frete_tipo' => 'pac',
            'frete_valor' => 14.50,
        ]);

        Pagamento::create([
            'pedido_id' => $pedido->id,
            'metodo' => 'pix',
            'gateway' => 'mercado_pago',
            'gateway_payment_id' => '555',
            'status' => 'pendente',
            'valor' => 104.50,
        ]);

        $this->mock(MercadoPagoService::class, function ($mock) use ($pedido): void {
            $mock->shouldReceive('getPayment')
                ->once()
                ->with('555')
                ->andReturn([
                    'id' => 555,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'transaction_amount' => 104.50,
                    'payment_method_id' => 'pix',
                    'payment_type_id' => 'pix',
                    'external_reference' => (string) $pedido->id,
                    'point_of_interaction' => [
                        'transaction_data' => [
                            'qr_code' => '000201...',
                        ],
                    ],
                ]);
        });

        $response = $this->postJson(route('site.checkout.mercadopago.webhook'), [
            'type' => 'payment',
            'data' => [
                'id' => '555',
            ],
        ]);

        $response->assertOk()->assertJson([
            'received' => true,
        ]);

        $pedido->refresh();
        $pagamento = Pagamento::where('pedido_id', $pedido->id)->firstOrFail();

        $this->assertSame('pago', $pedido->status);
        $this->assertSame('pago', $pagamento->status);
        $this->assertSame('accredited', $pagamento->gateway_status_detail);
        $this->assertSame('000201...', data_get($pagamento->payload, 'point_of_interaction.transaction_data.qr_code'));
    }

    public function test_webhook_rejects_invalid_signature_when_secret_is_configured(): void
    {
        config([
            'services.mercadopago.webhook_secret' => 'mp_test_secret',
        ]);

        $response = $this->postJson(
            route('site.checkout.mercadopago.webhook', ['data.id' => '555', 'type' => 'payment']),
            [
                'type' => 'payment',
                'data' => [
                    'id' => '555',
                ],
            ],
            [
                'X-Signature' => 'ts=1704908010,v1=invalid',
                'X-Request-Id' => 'req-invalid-signature',
            ]
        );

        $response->assertUnauthorized()->assertJson([
            'received' => false,
        ]);
    }

    public function test_webhook_accepts_valid_signature_when_secret_is_configured(): void
    {
        config([
            'services.mercadopago.webhook_secret' => 'mp_test_secret',
        ]);

        $user = User::factory()->create();
        $pedido = $this->makeCart($user, 90.00, 1, 'pendente');
        $pedido->update([
            'valor_total' => 104.50,
            'frete_tipo' => 'pac',
            'frete_valor' => 14.50,
        ]);

        Pagamento::create([
            'pedido_id' => $pedido->id,
            'metodo' => 'pix',
            'gateway' => 'mercado_pago',
            'gateway_payment_id' => '555',
            'status' => 'pendente',
            'valor' => 104.50,
        ]);

        $timestamp = '1704908010';
        $requestId = 'req-valid-signature';
        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', '555', $requestId, $timestamp);
        $signature = hash_hmac('sha256', $manifest, 'mp_test_secret');

        $this->mock(MercadoPagoService::class, function ($mock) use ($pedido): void {
            $mock->shouldReceive('getPayment')
                ->once()
                ->with('555')
                ->andReturn([
                    'id' => 555,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'transaction_amount' => 104.50,
                    'payment_method_id' => 'pix',
                    'payment_type_id' => 'pix',
                    'external_reference' => (string) $pedido->id,
                ]);
        });

        $response = $this->postJson(
            route('site.checkout.mercadopago.webhook', ['data.id' => '555', 'type' => 'payment']),
            [
                'type' => 'payment',
                'data' => [
                    'id' => '555',
                ],
            ],
            [
                'X-Signature' => 'ts=' . $timestamp . ',v1=' . $signature,
                'X-Request-Id' => $requestId,
            ]
        );

        $response->assertOk()->assertJson([
            'received' => true,
        ]);

        $pedido->refresh();

        $this->assertSame('pago', $pedido->status);
        $this->assertDatabaseHas('pagamentos', [
            'pedido_id' => $pedido->id,
            'gateway' => 'mercado_pago',
            'gateway_payment_id' => '555',
            'status' => 'pago',
            'gateway_status_detail' => 'accredited',
        ]);
    }

    public function test_webhook_accepts_valid_signature_with_production_id_and_topic_query_format(): void
    {
        config([
            'services.mercadopago.webhook_secret' => 'mp_test_secret',
        ]);

        $user = User::factory()->create();
        $pedido = $this->makeCart($user, 1.00, 1, 'pendente');
        $pedido->update([
            'valor_total' => 1.00,
            'frete_tipo' => 'retirada',
            'frete_valor' => 0.00,
        ]);

        Pagamento::create([
            'pedido_id' => $pedido->id,
            'metodo' => 'cartao',
            'gateway' => 'mercado_pago',
            'gateway_payment_id' => '152674547875',
            'status' => 'pendente',
            'valor' => 1.00,
        ]);

        $timestamp = '1775434680';
        $requestId = 'req-production-format';
        $paymentId = '152674547875';
        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', $paymentId, $requestId, $timestamp);
        $signature = hash_hmac('sha256', $manifest, 'mp_test_secret');

        $this->mock(MercadoPagoService::class, function ($mock) use ($pedido, $paymentId): void {
            $mock->shouldReceive('getPayment')
                ->once()
                ->with($paymentId)
                ->andReturn([
                    'id' => $paymentId,
                    'status' => 'in_process',
                    'status_detail' => 'in_process',
                    'transaction_amount' => 1.00,
                    'payment_method_id' => 'visa',
                    'payment_type_id' => 'credit_card',
                    'external_reference' => (string) $pedido->id,
                ]);
        });

        $response = $this->postJson(
            route('site.checkout.mercadopago.webhook', ['id' => $paymentId, 'topic' => 'payment']),
            [
                'resource' => $paymentId,
                'topic' => 'payment',
                'id' => $paymentId,
            ],
            [
                'X-Signature' => 'ts=' . $timestamp . ',v1=' . $signature,
                'X-Request-Id' => $requestId,
            ]
        );

        $response->assertOk()->assertJson([
            'received' => true,
        ]);

        $pedido->refresh();

        $this->assertSame('processando', $pedido->status);
        $this->assertDatabaseHas('pagamentos', [
            'pedido_id' => $pedido->id,
            'gateway' => 'mercado_pago',
            'gateway_payment_id' => $paymentId,
            'status' => 'pendente',
            'gateway_status_detail' => 'in_process',
        ]);
    }

    private function makeCart(User $user, float $preco, int $quantidade, string $status = 'carrinho'): Pedido
    {
        $produto = Produto::factory()->create([
            'preco' => $preco,
            'peso' => 0.5,
        ]);

        $pedido = Pedido::create([
            'user_id' => $user->id,
            'status' => $status,
            'valor_total' => $preco * $quantidade,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => $quantidade,
            'preco' => $preco,
        ]);

        return $pedido;
    }
}
