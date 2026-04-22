<?php

namespace Tests\Feature;

use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAnalyticsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_analytics_exports_require_authentication(): void
    {
        $this->get(route('admin.dashboard.exportar.csv'))
            ->assertRedirect(route('site.login'));

        $this->get(route('admin.dashboard.exportar.pdf'))
            ->assertRedirect(route('site.login'));
    }

    public function test_dashboard_analytics_csv_export_downloads_summary_and_product_rows(): void
    {
        $user = User::factory()->create(['admin' => true]);
        [$produtoPrincipal, $produtoSemCusto] = $this->seedAnalyticsData($user);

        $response = $this->actingAs($user)->get(route('admin.dashboard.exportar.csv'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('.csv', (string) $response->headers->get('content-disposition'));

        $content = $response->streamedContent();

        $this->assertStringContainsString('Resumo;Valor', $content);
        $this->assertStringContainsString('Receita Total', $content);
        $this->assertStringContainsString('R$ 240,00', $content);
        $this->assertStringContainsString('Produtos Analytics', $content);
        $this->assertStringContainsString($produtoPrincipal->nome, $content);
        $this->assertStringContainsString($produtoSemCusto->nome, $content);
        $this->assertStringContainsString('Sem custo', $content);
        $this->assertStringNotContainsString('Sem imagem', $content);
    }

    public function test_dashboard_analytics_pdf_export_downloads_pdf_file(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $this->seedAnalyticsData($user);

        $response = $this->actingAs($user)->get(route('admin.dashboard.exportar.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('content-disposition'));

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertStringStartsWith('%PDF-', $content);
    }

    private function seedAnalyticsData(User $user): array
    {
        $produtoPrincipal = Produto::factory()->create([
            'nome' => 'Produto Analytics A',
            'marca' => null,
            'preco' => 120.00,
            'custo_compra' => 80.00,
            'estoque' => 5,
            'ativo' => true,
        ]);

        $produtoSemCusto = Produto::factory()->create([
            'nome' => 'Produto Analytics B',
            'marca' => 'Marca B',
            'preco' => 90.00,
            'custo_compra' => null,
            'estoque' => 0,
            'ativo' => false,
        ]);

        $pedidoEntregue = Pedido::create([
            'user_id' => $user->id,
            'status' => 'entregue',
            'valor_total' => 240.00,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedidoEntregue->id,
            'produto_id' => $produtoPrincipal->id,
            'quantidade' => 2,
            'preco' => 120.00,
        ]);

        Pedido::create([
            'user_id' => $user->id,
            'status' => 'pendente',
            'valor_total' => 90.00,
        ]);

        return [$produtoPrincipal, $produtoSemCusto];
    }
}
