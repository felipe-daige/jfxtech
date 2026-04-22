<?php

namespace Tests\Feature;

use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_products_for_analytics(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $produto = Produto::factory()->create([
            'nome' => 'Wooting 60HE',
            'marca' => 'Wooting',
            'ativo' => true,
        ]);
        Produto::factory()->create([
            'nome' => 'Mouse Qualquer',
            'marca' => 'Outra',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.analytics.products.search', ['q' => 'woot']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $produto->id,
                'name' => 'Wooting 60HE',
                'brand' => 'Wooting',
                'slug' => $produto->slug,
                'active' => true,
                'url' => route('admin.analytics.products.show', $produto),
            ]);
    }

    public function test_non_admin_cannot_access_product_analytics_detail(): void
    {
        $user = User::factory()->create(['admin' => false]);
        $produto = Produto::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.analytics.products.show', $produto))
            ->assertForbidden();
    }

    public function test_product_analytics_detail_calculates_metrics_per_period(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $produto = Produto::factory()->create([
            'nome' => 'Teclado Analítico',
            'preco' => 120.00,
            'custo_compra' => 60.00,
            'ativo' => true,
        ]);

        $this->createOrderWithItem($admin, $produto, 2, 100.00, 'entregue', now()->subDays(10));
        $this->createOrderWithItem($admin, $produto, 1, 120.00, 'pago', now()->subDays(40));
        $this->createOrderWithItem($admin, $produto, 1, 90.00, 'enviado', now()->subDays(200));

        $response30 = $this->actingAs($admin)
            ->get(route('admin.analytics.products.show', ['produto' => $produto->id, 'period' => '30']));

        $response30->assertOk()
            ->assertViewHas('selected_period', '30')
            ->assertViewHas('product_metrics', function (array $metrics) {
                return $metrics['receita_total'] === 200.0
                    && $metrics['unidades_vendidas'] === 2
                    && $metrics['pedidos_count'] === 1
                    && $metrics['lucro_bruto_total'] === 80.0
                    && $metrics['margem_bruta_percentual'] === 40.0
                    && $metrics['ticket_medio_produto'] === 200.0;
            });

        $response90 = $this->actingAs($admin)
            ->get(route('admin.analytics.products.show', ['produto' => $produto->id, 'period' => '90']));

        $response90->assertOk()
            ->assertViewHas('selected_period', '90')
            ->assertViewHas('product_metrics', function (array $metrics) {
                return $metrics['receita_total'] === 320.0
                    && $metrics['unidades_vendidas'] === 3
                    && $metrics['pedidos_count'] === 2
                    && $metrics['lucro_bruto_total'] === 140.0
                    && $metrics['margem_bruta_percentual'] === 43.75
                    && $metrics['ticket_medio_produto'] === 160.0;
            });

        $responseTotal = $this->actingAs($admin)
            ->get(route('admin.analytics.products.show', ['produto' => $produto->id, 'period' => 'total']));

        $responseTotal->assertOk()
            ->assertViewHas('selected_period', 'total')
            ->assertViewHas('product_metrics', function (array $metrics) {
                return $metrics['receita_total'] === 410.0
                    && $metrics['unidades_vendidas'] === 4
                    && $metrics['pedidos_count'] === 3
                    && $metrics['lucro_bruto_total'] === 170.0
                    && $metrics['margem_bruta_percentual'] === 41.46
                    && $metrics['ticket_medio_produto'] === 136.67;
            });
    }

    public function test_product_analytics_detail_handles_product_without_sales(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $produto = Produto::factory()->create([
            'nome' => 'Produto Sem Venda',
            'custo_compra' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.products.show', ['produto' => $produto->id, 'period' => '30']))
            ->assertOk()
            ->assertViewHas('product_metrics', function (array $metrics) {
                return $metrics['receita_total'] === 0.0
                    && $metrics['unidades_vendidas'] === 0
                    && $metrics['pedidos_count'] === 0
                    && $metrics['lucro_bruto_total'] === 0.0
                    && $metrics['margem_bruta_percentual'] === 0.0
                    && $metrics['ticket_medio_produto'] === 0.0;
            });
    }

    private function createOrderWithItem(User $user, Produto $produto, int $quantidade, float $preco, string $status, \DateTimeInterface $createdAt): void
    {
        $pedido = Pedido::create([
            'user_id' => $user->id,
            'status' => $status,
            'valor_total' => $preco * $quantidade,
        ]);

        $pedido->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => $quantidade,
            'preco' => $preco,
        ]);
    }
}
