<?php

namespace Tests\Feature;

use App\Models\ItemPedido;
use App\Models\Pagamento;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\ProdutoVariante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderDetailsAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_details_modal_renders_margin_analytics_with_variant_and_product_costs(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create([
            'status' => 'pago',
            'valor_total' => 540.00,
            'frete_valor' => 20.00,
            'valor_desconto' => 10.00,
            'checkout_mode' => 'guest',
            'customer_name' => 'Cliente BI',
            'customer_email' => 'cliente-bi@example.com',
        ]);

        $produtoComVariante = Produto::factory()->create([
            'nome' => 'Mouse Competition',
            'preco' => 250.00,
            'custo_compra' => 180.00,
        ]);

        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produtoComVariante->id,
            'custo_compra' => 120.00,
        ]);

        $produtoFallback = Produto::factory()->create([
            'nome' => 'Teclado Hall Effect',
            'preco' => 280.00,
            'custo_compra' => 90.00,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produtoComVariante->id,
            'produto_variante_id' => $variante->id,
            'quantidade' => 1,
            'preco' => 200.00,
            'opcoes_snapshot' => ['Cor' => 'Branco'],
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produtoFallback->id,
            'quantidade' => 2,
            'preco' => 150.00,
        ]);

        Pagamento::create([
            'pedido_id' => $pedido->id,
            'metodo' => 'pix',
            'status' => 'pago',
            'valor' => 540.00,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.pedidos.detalhes', $pedido->id));

        $response->assertOk()
            ->assertJsonStructure(['html']);

        $html = html_entity_decode((string) $response->json('html'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->assertStringContainsString('Order Intelligence', $html);
        $this->assertStringContainsString('Margem por produto no pedido', $html);
        $this->assertStringContainsString('Mouse Competition', $html);
        $this->assertStringContainsString('Teclado Hall Effect', $html);
        $this->assertStringContainsString('COR: BRANCO', mb_strtoupper($html));
        $this->assertStringContainsString('TOP LUCRO', mb_strtoupper($html));
        $this->assertStringContainsString('PIOR MARGEM', mb_strtoupper($html));
        $this->assertStringContainsString('VARIANTE', $html);
        $this->assertStringContainsString('PRODUTO', $html);
        $this->assertStringContainsString('R$ 300,00', $html);
        $this->assertStringContainsString('R$ 180,00', $html);
    }

    public function test_order_details_modal_flags_missing_cost_items_and_partial_profit(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create([
            'status' => 'processando',
            'valor_total' => 190.00,
            'frete_valor' => 15.00,
            'valor_desconto' => 0.00,
        ]);

        $produtoSemCusto = Produto::factory()->create([
            'nome' => 'Headset sem custo',
            'preco' => 190.00,
            'custo_compra' => null,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produtoSemCusto->id,
            'quantidade' => 1,
            'preco' => 175.00,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.pedidos.detalhes', $pedido->id));

        $response->assertOk();

        $html = html_entity_decode((string) $response->json('html'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->assertStringContainsString('Sem custo', $html);
        $this->assertStringContainsString('Sem custo cadastrado para este SKU', $html);
        $this->assertStringContainsString('item(ns) sem custo cadastrado', $html);
        $this->assertStringContainsString('O lucro exibido é parcial', $html);
    }

    public function test_order_details_modal_requires_authentication(): void
    {
        $pedido = Pedido::factory()->create();

        $this->get(route('admin.pedidos.detalhes', $pedido->id))
            ->assertRedirect(route('site.login'));
    }
}
