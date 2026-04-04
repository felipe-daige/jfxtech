<?php

namespace Tests\Feature;

use App\Models\ItemPedido;
use App\Models\Pagamento;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ]);
    }

    public function test_prepare_updates_order_and_returns_checkout_payload(): void
    {
        $user = User::factory()->create([
            'phone' => '(43) 99999-9999',
        ]);

        $pedido = $this->makeCart($user, 100.00, 2);

        $response = $this->actingAs($user)->postJson(route('site.checkout.mercadopago.prepare'), [
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
            ->assertJsonPath('checkout.amount', 218);

        $pedido->refresh();

        $this->assertSame('pendente', $pedido->status);
        $this->assertSame('pac', $pedido->frete_tipo);
        $this->assertEquals(18.00, (float) $pedido->frete_valor);
        $this->assertEquals(218.00, (float) $pedido->valor_total);
        $this->assertNotNull($pedido->endereco_id);
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
