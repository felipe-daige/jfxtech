<?php

namespace Tests\Feature;

use App\Models\Endereco;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalizar_compra_loads_pending_order_and_restores_saved_shipping_selection(): void
    {
        $user = User::factory()->create();
        $pedido = $this->makeOrder($user, 'pendente');
        $pedido->update([
            'frete_tipo' => 'sedex',
            'frete_valor' => 32.00,
        ]);

        Endereco::create([
            'user_id' => $user->id,
            'cep' => '86010000',
            'rua' => 'Rua Teste',
            'numero' => '123',
            'bairro' => 'Centro',
            'cidade' => 'Londrina',
            'estado' => 'PR',
            'pais' => 'BR',
        ]);

        $response = $this->actingAs($user)->get(route('site.checkout'));

        $response->assertOk();
        $response->assertSee("const savedFreteType = \"sedex\";", false);
        $response->assertSee('const savedFreteValue = 32;', false);
    }

    public function test_frete_endpoint_works_with_pending_order(): void
    {
        $user = User::factory()->create();
        $this->makeOrder($user, 'pendente');

        $response = $this->actingAs($user)->postJson(route('frete.carrinho'), [
            'cep_destino' => '86010000',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(18.00, (float) $response->json('opcoes.pac.valor'));
        $this->assertEquals(27.00, (float) $response->json('opcoes.sedex.valor'));
    }

    public function test_cart_endpoints_fallback_to_pending_order_when_no_open_cart_exists(): void
    {
        $user = User::factory()->create();
        $pedido = $this->makeOrder($user, 'pendente', 2);

        $this->actingAs($user)
            ->getJson(route('site.carrinho.contador'))
            ->assertOk()
            ->assertJsonPath('total_itens', 2);

        $this->actingAs($user)
            ->getJson(route('site.carrinho.itens'))
            ->assertOk()
            ->assertJsonPath('carrinho.id', $pedido->id)
            ->assertJsonPath('carrinho.itens.0.quantidade', 2);
    }

    private function makeOrder(User $user, string $status, int $quantidade = 1): Pedido
    {
        $produto = Produto::factory()->create([
            'preco' => 120.00,
            'peso' => 0.7,
        ]);

        $pedido = Pedido::create([
            'user_id' => $user->id,
            'status' => $status,
            'valor_total' => 120.00 * $quantidade,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => $quantidade,
            'preco' => 120.00,
        ]);

        return $pedido;
    }
}
