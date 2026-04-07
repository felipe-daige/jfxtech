<?php

namespace Tests\Feature;

use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use App\Services\CheckoutOrderService;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
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

    public function test_guest_can_add_items_and_access_checkout_page(): void
    {
        $produto = Produto::factory()->create([
            'preco' => 120.00,
            'estoque' => 10,
            'ativo' => true,
        ]);

        $this->postJson(route('site.carrinho.adicionar'), [
            'produto_id' => $produto->id,
            'quantidade' => 1,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('carrinho.total_itens', 1);

        $this->get(route('site.checkout'))
            ->assertOk()
            ->assertSee('Dados do Comprador')
            ->assertSee('Continuar para Pagamento');
    }

    public function test_guest_can_prepare_and_pay_without_authentication(): void
    {
        $pedido = $this->makeGuestCart();

        $this->withSession([
            CheckoutOrderService::SESSION_ORDER_ID => $pedido->id,
            CheckoutOrderService::SESSION_ORDER_TOKEN => $pedido->guest_token,
        ])->postJson(route('site.checkout.mercadopago.prepare'), [
            'customer_name' => 'Cliente Convidado',
            'customer_email' => 'guest@example.com',
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
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('checkout.customer.email', 'guest@example.com');

        $pedido->refresh();

        $this->mock(MercadoPagoService::class, function ($mock) use ($pedido): void {
            $mock->shouldReceive('createPayment')
                ->once()
                ->andReturn([
                    'id' => 123456789,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'transaction_amount' => (float) $pedido->valor_total,
                    'payment_method_id' => 'visa',
                    'payment_type_id' => 'credit_card',
                    'external_reference' => (string) $pedido->id,
                ]);
        });

        $this->withSession([
            CheckoutOrderService::SESSION_ORDER_ID => $pedido->id,
            CheckoutOrderService::SESSION_ORDER_TOKEN => $pedido->guest_token,
        ])->postJson(route('site.checkout.mercadopago.pay'), [
            'pedido_id' => $pedido->id,
            'payment_method_id' => 'visa',
            'transaction_amount' => (float) $pedido->valor_total,
            'installments' => 1,
            'token' => 'test-token',
            'issuer_id' => '123',
            'payer' => [
                'email' => 'guest@example.com',
                'identification' => [
                    'type' => 'CPF',
                    'number' => '12345678901',
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment.redirect_url', route('site.pedidos.show', $pedido) . '?token=' . $pedido->guest_token);
    }

    public function test_guest_can_create_account_after_paid_order(): void
    {
        $pedido = $this->makeGuestCart('pago');
        $pedido->update([
            'customer_name' => 'Cliente Convidado',
            'customer_email' => 'novo@example.com',
            'customer_phone' => '(43) 99999-9999',
        ]);

        $response = $this->withSession([
            CheckoutOrderService::SESSION_ORDER_ID => $pedido->id,
            CheckoutOrderService::SESSION_ORDER_TOKEN => $pedido->guest_token,
        ])->post(route('site.pedidos.create-account', $pedido), [
            'guest_token' => $pedido->guest_token,
            'password' => 'senha-super-segura',
            'password_confirmation' => 'senha-super-segura',
        ]);

        $response->assertRedirect(route('site.pedidos.show', $pedido));

        $pedido->refresh();
        $user = User::where('email', 'novo@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame($user->id, $pedido->user_id);
        $this->assertAuthenticatedAs($user);
    }

    private function makeGuestCart(string $status = 'carrinho'): Pedido
    {
        $produto = Produto::factory()->create([
            'preco' => 100.00,
            'peso' => 0.7,
        ]);

        $pedido = Pedido::create([
            'status' => $status,
            'valor_total' => 100.00,
            'guest_token' => 'guest-token-' . uniqid(),
            'checkout_mode' => 'guest',
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco' => 100.00,
        ]);

        return $pedido;
    }
}
