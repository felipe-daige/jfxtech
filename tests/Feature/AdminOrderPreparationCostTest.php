<?php

namespace Tests\Feature;

use App\Enums\PedidoStatus;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\ProdutoVariante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOrderPreparationCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_must_declare_item_costs_when_marking_order_as_processing(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create(['status' => PedidoStatus::PAGO]);

        $produto = Produto::factory()->create(['custo_compra' => 50.00]);
        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco' => 120.00,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.pedidos'))
            ->post(route('admin.pedidos.status', $pedido), [
                'status' => PedidoStatus::PROCESSANDO,
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'status' => PedidoStatus::PAGO,
        ]);
    }

    public function test_admin_can_mark_order_as_processing_with_declared_costs_and_invoice_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create(['status' => PedidoStatus::PAGO]);
        $produto = Produto::factory()->create(['custo_compra' => 50.00]);
        $item = ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 2,
            'preco' => 120.00,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pedidos.status', $pedido), [
                'status' => PedidoStatus::PROCESSANDO,
                'itens' => [
                    $item->id => ['custo_unitario_declarado' => '72,35'],
                ],
                'nota_fiscal_imagem' => UploadedFile::fake()->image('nota.png'),
            ])
            ->assertRedirect(route('admin.pedidos'));

        $pedido->refresh();
        $item->refresh();

        $this->assertSame(PedidoStatus::PROCESSANDO, $pedido->status);
        $this->assertSame('72.35', $item->custo_unitario_declarado);
        $this->assertNotNull($item->custo_declarado_em);
        $this->assertNotNull($pedido->nota_fiscal_imagem_path);
        Storage::disk('public')->assertExists($pedido->nota_fiscal_imagem_path);
    }

    public function test_quick_processing_status_can_use_catalog_costs_as_shortcut(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create(['status' => PedidoStatus::PAGO]);
        $produto = Produto::factory()->create([
            'custo_compra' => 50.00,
            'frete_compra' => 7.50,
            'peso' => null,
        ]);
        $item = ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco' => 120.00,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.pedidos.quick-status', $pedido), [
                'status' => PedidoStatus::PROCESSANDO,
                'use_catalog_costs' => true,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => PedidoStatus::PROCESSANDO,
            ]);

        $item->refresh();

        $this->assertSame('57.50', $item->custo_unitario_declarado);
        $this->assertSame(ItemPedido::STATUS_PREPARACAO_CONFIRMADO, $item->status_preparacao);
    }

    public function test_admin_can_update_preparation_data_for_order_already_processing(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create(['status' => PedidoStatus::PROCESSANDO]);
        $produto = Produto::factory()->create(['custo_compra' => 50.00]);
        $item = ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco' => 120.00,
            'custo_unitario_declarado' => 50.00,
            'custo_declarado_em' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pedidos.preparacao', $pedido), [
                'itens' => [
                    $item->id => ['custo_unitario_declarado' => '64,90'],
                ],
                'nota_fiscal_imagem' => UploadedFile::fake()->image('comprovante.png'),
            ])
            ->assertRedirect(route('admin.pedidos.detalhes', $pedido));

        $pedido->refresh();
        $item->refresh();

        $this->assertSame(PedidoStatus::PROCESSANDO, $pedido->status);
        $this->assertSame('64.90', $item->custo_unitario_declarado);
        $this->assertNotNull($pedido->nota_fiscal_imagem_path);
        Storage::disk('public')->assertExists($pedido->nota_fiscal_imagem_path);
    }

    public function test_admin_can_confirm_one_item_and_cancel_another_in_preparation(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create(['status' => PedidoStatus::PROCESSANDO]);
        $produtoConfirmado = Produto::factory()->create(['nome' => 'Mouse entregue', 'custo_compra' => 80.00]);
        $produtoCancelado = Produto::factory()->create(['nome' => 'Teclado cancelado', 'custo_compra' => 120.00]);

        $itemConfirmado = ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produtoConfirmado->id,
            'quantidade' => 1,
            'preco' => 200.00,
        ]);
        $itemCancelado = ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produtoCancelado->id,
            'quantidade' => 1,
            'preco' => 300.00,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pedidos.preparacao', $pedido), [
                'itens' => [
                    $itemConfirmado->id => [
                        'status_preparacao' => ItemPedido::STATUS_PREPARACAO_CONFIRMADO,
                        'custo_unitario_declarado' => '90,00',
                    ],
                    $itemCancelado->id => [
                        'status_preparacao' => ItemPedido::STATUS_PREPARACAO_CANCELADO,
                        'custo_unitario_declarado' => '',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.pedidos.detalhes', $pedido));

        $itemConfirmado->refresh();
        $itemCancelado->refresh();

        $this->assertSame(ItemPedido::STATUS_PREPARACAO_CONFIRMADO, $itemConfirmado->status_preparacao);
        $this->assertSame('90.00', $itemConfirmado->custo_unitario_declarado);
        $this->assertSame(ItemPedido::STATUS_PREPARACAO_CANCELADO, $itemCancelado->status_preparacao);
        $this->assertNull($itemCancelado->custo_unitario_declarado);
    }

    public function test_orders_page_renders_real_cost_tab(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create([
            'status' => PedidoStatus::PROCESSANDO,
            'valor_total' => 200.00,
        ]);
        $produto = Produto::factory()->create([
            'nome' => 'Produto custo real na lista',
            'custo_compra' => 80.00,
            'peso' => null,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco' => 200.00,
            'status_preparacao' => ItemPedido::STATUS_PREPARACAO_CONFIRMADO,
            'custo_unitario_declarado' => 95.00,
            'custo_declarado_em' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pedidos'))
            ->assertOk()
            ->assertSeeText('Custo Real')
            ->assertSeeText('Produto custo real na lista')
            ->assertSeeText('+ R$ 15,00');
    }

    public function test_orders_page_renders_real_cost_tab_for_items_with_variants(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create([
            'status' => PedidoStatus::PROCESSANDO,
            'valor_total' => 200.00,
        ]);
        $produto = Produto::factory()->create([
            'nome' => 'Produto com variante',
            'custo_compra' => 80.00,
            'peso' => null,
        ]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'custo_compra' => 80.00,
            'frete_compra' => 5.00,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'produto_variante_id' => $variante->id,
            'quantidade' => 1,
            'preco' => 200.00,
            'status_preparacao' => ItemPedido::STATUS_PREPARACAO_CONFIRMADO,
            'custo_unitario_declarado' => 95.00,
            'custo_declarado_em' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pedidos'))
            ->assertOk()
            ->assertSeeText('Custo Real')
            ->assertSeeText('Produto com variante');
    }
}
