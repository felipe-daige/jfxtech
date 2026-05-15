<?php

namespace Tests\Feature;

use App\Models\CustoOperacional;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOperationalCostAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_operational_cost_from_analytics(): void
    {
        $admin = User::factory()->create(['admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.analytics.custos.store'), [
                'tipo' => 'transporte',
                'nome' => 'Transporte fornecedor',
                'valor' => '35,90',
                'data_referencia' => '2026-05-01',
            ])
            ->assertRedirect(route('admin.analytics'));

        $custo = CustoOperacional::first();

        $this->assertSame($admin->id, $custo->created_by);
        $this->assertSame('transporte', $custo->tipo);
        $this->assertSame('Transporte fornecedor', $custo->nome);
        $this->assertSame('35.90', $custo->valor);
        $this->assertSame('2026-05-01', $custo->data_referencia->toDateString());
    }

    public function test_analytics_manager_cannot_create_operational_costs(): void
    {
        $manager = User::factory()->create([
            'admin' => false,
            'admin_permissions' => [User::ADMIN_PERMISSION_ANALYTICS],
        ]);

        $this->actingAs($manager)
            ->post(route('admin.analytics.custos.store'), [
                'tipo' => 'transporte',
                'nome' => 'Transporte fornecedor',
                'valor' => '35,90',
                'data_referencia' => '2026-05-01',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('custos_operacionais', 0);
    }

    public function test_dashboard_analytics_subtracts_operational_costs_from_net_profit(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $produto = Produto::factory()->create(['custo_compra' => 100.00, 'peso' => null]);
        $pedido = Pedido::create([
            'user_id' => $admin->id,
            'status' => 'entregue',
            'valor_total' => 200.00,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco' => 200.00,
        ]);

        CustoOperacional::create([
            'created_by' => $admin->id,
            'tipo' => 'transporte',
            'nome' => 'Transporte',
            'valor' => 30.00,
            'data_referencia' => '2026-05-01',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertViewHas('custos_operacionais_total', 30.0)
            ->assertViewHas('lucro_bruto_total', 100.0)
            ->assertViewHas('lucro_liquido_total', 70.0)
            ->assertViewHas('margem_liquida_percentual', 35.0);
    }

    public function test_dashboard_analytics_compares_real_costs_and_counts_canceled_items_as_refunds(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $produtoConfirmado = Produto::factory()->create(['nome' => 'Produto confirmado', 'custo_compra' => 80.00, 'peso' => null]);
        $produtoCancelado = Produto::factory()->create(['nome' => 'Produto estornado', 'custo_compra' => 120.00, 'peso' => null]);
        $pedido = Pedido::create([
            'user_id' => $admin->id,
            'status' => 'processando',
            'valor_total' => 500.00,
            'frete_valor' => 0.00,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produtoConfirmado->id,
            'quantidade' => 1,
            'preco' => 200.00,
            'status_preparacao' => ItemPedido::STATUS_PREPARACAO_CONFIRMADO,
            'custo_unitario_declarado' => 90.00,
            'custo_declarado_em' => now(),
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produtoCancelado->id,
            'quantidade' => 1,
            'preco' => 300.00,
            'status_preparacao' => ItemPedido::STATUS_PREPARACAO_CANCELADO,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertViewHas('receita_total', 200.0)
            ->assertViewHas('estornos_total', 300.0)
            ->assertViewHas('cost_comparison', function (array $comparison) {
                return $comparison['summary']['real_total'] === 90.0
                    && $comparison['summary']['catalog_total'] === 80.0
                    && $comparison['summary']['delta_total'] === 10.0;
            });
    }
}
