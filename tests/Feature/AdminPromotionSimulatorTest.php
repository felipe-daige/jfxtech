<?php

namespace Tests\Feature;

use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\ProdutoVariante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPromotionSimulatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotion_simulator_requires_authentication(): void
    {
        $produto = Produto::factory()->create();

        $this->postJson(route('admin.dashboard.simulator'), [
            'product_ids' => [$produto->id],
            'period_days' => 30,
            'discount_percent' => 10,
            'extra_unit_cost' => 0,
            'extra_order_cost' => 0,
        ])->assertStatus(401);
    }

    public function test_simulator_page_displays_promotion_simulator_section(): void
    {
        $user = User::factory()->create(['admin' => true]);
        Produto::factory()->create(['nome' => 'Produto Simulador']);

        $this->actingAs($user)
            ->get(route('admin.simulador'))
            ->assertOk()
            ->assertSee('Simulador de Promoção')
            ->assertSee('Rodar simulação');
    }

    public function test_simulator_returns_consolidated_metrics_for_selected_products(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $produtoA = Produto::factory()->create([
            'nome' => 'Produto A',
            'preco' => 120.00,
            'custo_compra' => 80.00,
            'peso' => null,
        ]);
        $produtoB = Produto::factory()->create([
            'nome' => 'Produto B',
            'preco' => 90.00,
            'custo_compra' => 50.00,
            'peso' => null,
        ]);

        $pedido = $this->createPedido($user, 330.00, now()->subDays(5));

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produtoA->id,
            'quantidade' => 2,
            'preco' => 120.00,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produtoB->id,
            'quantidade' => 1,
            'preco' => 90.00,
        ]);

        $response = $this->actingAs($user)->postJson(route('admin.dashboard.simulator'), [
            'product_ids' => [$produtoA->id, $produtoB->id],
            'period_days' => 30,
            'discount_percent' => 10,
            'extra_unit_cost' => 5,
            'extra_order_cost' => 10,
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.products_count', 2)
            ->assertJsonPath('meta.selected_units', 3)
            ->assertJsonPath('meta.selected_orders', 1);

        $payload = $response->json();

        $this->assertEquals(330.0, $payload['summary']['current']['revenue_total']);
        $this->assertEquals(210.0, $payload['summary']['current']['cost_total']);
        $this->assertEquals(120.0, $payload['summary']['current']['profit_total']);
        $this->assertEquals(297.0, $payload['summary']['simulated']['revenue_total']);
        $this->assertEquals(235.0, $payload['summary']['simulated']['cost_total']);
        $this->assertEquals(62.0, $payload['summary']['simulated']['profit_total']);
        $this->assertEquals(-58.0, $payload['summary']['delta']['profit_total']);
    }

    public function test_simulator_supports_all_selected_products_and_flags_missing_costs(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $produtoComHistorico = Produto::factory()->create([
            'nome' => 'Produto Histórico',
            'preco' => 100.00,
            'custo_compra' => 60.00,
            'peso' => null,
        ]);
        $produtoSemCusto = Produto::factory()->create([
            'nome' => 'Produto Sem Custo',
            'preco' => 70.00,
            'custo_compra' => null,
            'peso' => null,
        ]);

        $pedido = $this->createPedido($user, 100.00, now()->subDays(3));

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produtoComHistorico->id,
            'quantidade' => 1,
            'preco' => 100.00,
        ]);

        $response = $this->actingAs($user)->postJson(route('admin.dashboard.simulator'), [
            'product_ids' => [$produtoComHistorico->id, $produtoSemCusto->id],
            'period_days' => 30,
            'discount_percent' => 0,
            'extra_unit_cost' => 0,
            'extra_order_cost' => 0,
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.products_count', 2)
            ->assertJsonPath('products.0.units', 1)
            ->assertJsonPath('products.1.units', 0)
            ->assertJsonPath('products.1.missing_cost', true);

        $this->assertStringContainsString(
            'não têm custo cadastrado',
            collect($response->json('alerts'))->pluck('message')->implode(' | ')
        );
    }

    public function test_simulator_uses_variant_cost_and_reports_negative_simulated_margin(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $produto = Produto::factory()->create([
            'nome' => 'Produto Variante',
            'preco' => 150.00,
            'custo_compra' => 100.00,
            'peso' => null,
        ]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'custo_compra' => 130.00,
            'preco' => 150.00,
        ]);

        $pedido = $this->createPedido($user, 150.00, now()->subDays(2));

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'produto_variante_id' => $variante->id,
            'quantidade' => 1,
            'preco' => 150.00,
        ]);

        $response = $this->actingAs($user)->postJson(route('admin.dashboard.simulator'), [
            'product_ids' => [$produto->id],
            'period_days' => 30,
            'discount_percent' => 20,
            'extra_unit_cost' => 0,
            'extra_order_cost' => 0,
        ]);

        $response->assertOk();

        $payload = $response->json();

        $this->assertEquals(130.0, $payload['products'][0]['average_base_cost_per_unit']);
        $this->assertEquals(130.0, $payload['products'][0]['current_cost_total']);
        $this->assertEquals(-10.0, $payload['products'][0]['simulated_profit_total']);
        $this->assertEquals(-8.33, $payload['products'][0]['simulated_margin_percent']);

        $this->assertStringContainsString(
            'margem negativa',
            collect($response->json('alerts'))->pluck('message')->implode(' | ')
        );
    }

    private function createPedido(User $user, float $valorTotal, \DateTimeInterface $createdAt): Pedido
    {
        $pedido = Pedido::create([
            'user_id' => $user->id,
            'status' => 'entregue',
            'valor_total' => $valorTotal,
        ]);

        $pedido->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $pedido;
    }
}
