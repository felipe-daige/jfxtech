<?php

namespace Tests\Feature;

use App\Models\Cupom;
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
            ->assertSee('ENDEREÇO DE ENTREGA')
            ->assertSee('Informe o CEP')
            ->assertSee('Continuar para Pagamento');
    }

    public function test_guest_coupon_link_survives_product_selection_until_checkout(): void
    {
        Cupom::create([
            'codigo' => 'FELIPE',
            'tipo' => 'percentual',
            'valor' => 5,
            'ativo' => true,
        ]);

        $produto = Produto::factory()->create([
            'preco' => 200.00,
            'estoque' => 10,
            'ativo' => true,
        ]);

        $this->get('/?cupom=FELIPE')->assertOk();

        $this->postJson(route('site.carrinho.adicionar'), [
            'produto_id' => $produto->id,
            'quantidade' => 1,
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->get(route('site.checkout'))
            ->assertOk()
            ->assertSee('Continuar para Pagamento');

        $pedido = Pedido::whereNull('user_id')->latest('id')->first();

        $this->assertNotNull($pedido);
        $this->assertSame('FELIPE', $pedido->cupom_codigo);
        $this->assertEquals('10.00', $pedido->valor_desconto);
    }

    public function test_guest_cart_counter_and_items_are_available_without_login(): void
    {
        $produto = Produto::factory()->create([
            'preco' => 120.00,
            'estoque' => 10,
            'ativo' => true,
        ]);

        $this->postJson(route('site.carrinho.adicionar'), [
            'produto_id' => $produto->id,
            'quantidade' => 2,
        ])->assertOk();

        $this->getJson(route('site.carrinho.contador'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_itens', 2);

        $this->getJson(route('site.carrinho.itens'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('carrinho.itens.0.produto.id', $produto->id);
    }

    public function test_login_converts_guest_cart_to_authenticated_cart(): void
    {
        $user = User::factory()->create(['email' => 'cliente@example.com']);
        $produto = Produto::factory()->create([
            'preco' => 120.00,
            'estoque' => 10,
            'ativo' => true,
        ]);

        $this->postJson(route('site.carrinho.adicionar'), [
            'produto_id' => $produto->id,
            'quantidade' => 1,
        ])->assertOk();

        $guestCart = Pedido::whereNull('user_id')->where('status', 'carrinho')->latest('id')->first();
        $this->assertNotNull($guestCart);

        $this->post(route('site.login.post'), [
            'email' => 'cliente@example.com',
            'password' => 'password',
        ])->assertRedirect(route('site.index'));

        $guestCart->refresh();

        $this->assertSame($user->id, $guestCart->user_id);
        $this->assertNull($guestCart->guest_token);
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, $guestCart->itens()->where('produto_id', $produto->id)->count());
    }

    public function test_register_converts_guest_cart_to_authenticated_cart(): void
    {
        $produto = Produto::factory()->create([
            'preco' => 120.00,
            'estoque' => 10,
            'ativo' => true,
        ]);

        $this->postJson(route('site.carrinho.adicionar'), [
            'produto_id' => $produto->id,
            'quantidade' => 1,
        ])->assertOk();

        $this->post(route('site.register.post'), [
            'name' => 'Cliente Novo',
            'email' => 'novo-carrinho@example.com',
            'phone' => '(43) 99999-9999',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ])->assertRedirect(route('site.index'));

        $user = User::where('email', 'novo-carrinho@example.com')->first();
        $this->assertNotNull($user);

        $cart = Pedido::where('user_id', $user->id)->where('status', 'carrinho')->first();

        $this->assertNotNull($cart);
        $this->assertNull($cart->guest_token);
        $this->assertSame(1, $cart->itens()->where('produto_id', $produto->id)->count());
    }

    public function test_login_merges_guest_cart_into_existing_user_cart(): void
    {
        $user = User::factory()->create(['email' => 'merge@example.com']);
        $produto = Produto::factory()->create([
            'preco' => 120.00,
            'estoque' => 10,
            'ativo' => true,
        ]);

        $userCart = Pedido::create([
            'user_id' => $user->id,
            'status' => 'carrinho',
            'valor_total' => 120.00,
            'checkout_mode' => 'authenticated',
        ]);

        ItemPedido::create([
            'pedido_id' => $userCart->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco' => 120.00,
        ]);

        $this->postJson(route('site.carrinho.adicionar'), [
            'produto_id' => $produto->id,
            'quantidade' => 2,
        ])->assertOk();

        $guestCart = Pedido::whereNull('user_id')->where('status', 'carrinho')->latest('id')->first();
        $this->assertNotNull($guestCart);

        $this->post(route('site.login.post'), [
            'email' => 'merge@example.com',
            'password' => 'password',
        ])->assertRedirect(route('site.index'));

        $userCart->refresh();

        $this->assertDatabaseMissing('pedidos', ['id' => $guestCart->id]);
        $this->assertSame(1, $userCart->itens()->where('produto_id', $produto->id)->count());
        $this->assertSame(3, $userCart->itens()->where('produto_id', $produto->id)->first()->quantidade);
        $this->assertEquals('360.00', $userCart->valor_total);
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
