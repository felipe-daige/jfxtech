<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Produto;
use App\Models\ProdutoOpcaoGrupo;
use App\Models\ProdutoOpcaoValor;
use App\Models\ProdutoVariante;
use App\Models\Pedido;
use App\Models\ItemPedido;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CarrinhoVarianteTest extends TestCase
{
    use RefreshDatabase;

    private function loginUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        return $user;
    }

    private function makeVariante(array $produtoAttrs = [], array $varianteAttrs = []): ProdutoVariante
    {
        $produto = Produto::factory()->create(array_merge(['estoque' => 10, 'estoque_compartilhado' => true], $produtoAttrs));
        $grupo = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id, 'nome' => 'Cor']);
        $valor = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id, 'valor' => 'Preto']);
        return ProdutoVariante::factory()->create(array_merge([
            'produto_id' => $produto->id,
            'valores'    => [$valor->id],
        ], $varianteAttrs));
    }

    public function test_add_to_cart_with_variante_creates_item_with_variante_id(): void
    {
        $this->loginUser();
        $variante = $this->makeVariante([], ['preco' => 149.90]);

        $response = $this->postJson('/carrinho/adicionar', [
            'produto_id'          => $variante->produto_id,
            'produto_variante_id' => $variante->id,
            'quantidade'          => 1,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('itens_pedido', [
            'produto_id'          => $variante->produto_id,
            'produto_variante_id' => $variante->id,
            'preco'               => 149.90,
        ]);
    }

    public function test_same_produto_different_variante_creates_separate_cart_items(): void
    {
        $this->loginUser();
        $produto = Produto::factory()->create(['estoque' => 10, 'estoque_compartilhado' => true]);
        $grupo   = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id]);
        $v1Val   = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id, 'valor' => 'Preto']);
        $v2Val   = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id, 'valor' => 'Branco']);
        $var1    = ProdutoVariante::factory()->create(['produto_id' => $produto->id, 'valores' => [$v1Val->id]]);
        $var2    = ProdutoVariante::factory()->create(['produto_id' => $produto->id, 'valores' => [$v2Val->id]]);

        $this->postJson('/carrinho/adicionar', ['produto_id' => $produto->id, 'produto_variante_id' => $var1->id, 'quantidade' => 1]);
        $this->postJson('/carrinho/adicionar', ['produto_id' => $produto->id, 'produto_variante_id' => $var2->id, 'quantidade' => 1]);

        $carrinho = Pedido::where('user_id', auth()->id())->where('status', 'carrinho')->first();
        $this->assertCount(2, $carrinho->itens);
    }

    public function test_adding_same_variante_twice_increments_quantity_not_new_row(): void
    {
        $this->loginUser();
        $variante = $this->makeVariante([], ['preco' => 99.00]);

        $this->postJson('/carrinho/adicionar', ['produto_id' => $variante->produto_id, 'produto_variante_id' => $variante->id, 'quantidade' => 1]);
        $this->postJson('/carrinho/adicionar', ['produto_id' => $variante->produto_id, 'produto_variante_id' => $variante->id, 'quantidade' => 1]);

        $carrinho = Pedido::where('user_id', auth()->id())->where('status', 'carrinho')->first();
        $this->assertCount(1, $carrinho->itens);
        $this->assertEquals(2, $carrinho->itens->first()->quantidade);
        // preco must NOT be refreshed on increment
        $this->assertEquals(99.00, $carrinho->itens->first()->preco);
    }

    public function test_rejects_variante_belonging_to_different_produto(): void
    {
        $this->loginUser();
        $produto1   = Produto::factory()->create(['estoque' => 10]);
        $produto2   = Produto::factory()->create(['estoque' => 10]);
        $grupo      = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto2->id]);
        $valor      = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id]);
        $variantep2 = ProdutoVariante::factory()->create(['produto_id' => $produto2->id, 'valores' => [$valor->id]]);

        $response = $this->postJson('/carrinho/adicionar', [
            'produto_id'          => $produto1->id,
            'produto_variante_id' => $variantep2->id,
            'quantidade'          => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseMissing('itens_pedido', ['produto_id' => $produto1->id]);
    }

    public function test_opcoes_snapshot_is_built_server_side(): void
    {
        $this->loginUser();
        $variante = $this->makeVariante();

        $this->postJson('/carrinho/adicionar', [
            'produto_id'          => $variante->produto_id,
            'produto_variante_id' => $variante->id,
            'quantidade'          => 1,
        ]);

        $item = ItemPedido::where('produto_variante_id', $variante->id)->first();
        $this->assertNotNull($item->opcoes_snapshot);
        $this->assertIsArray($item->opcoes_snapshot);
        $this->assertArrayHasKey('Cor', $item->opcoes_snapshot);
    }

    public function test_itens_response_includes_opcoes_snapshot(): void
    {
        $this->loginUser();
        $variante = $this->makeVariante();

        $this->postJson('/carrinho/adicionar', [
            'produto_id'          => $variante->produto_id,
            'produto_variante_id' => $variante->id,
            'quantidade'          => 1,
        ]);

        $response = $this->getJson('/carrinho/itens');
        $response->assertOk();
        $itens = $response->json('carrinho.itens');
        $this->assertArrayHasKey('opcoes_snapshot', $itens[0]);
        $this->assertNotNull($itens[0]['opcoes_snapshot']);
        $this->assertArrayHasKey('Cor', $itens[0]['opcoes_snapshot']);
    }

    public function test_atualizar_quantidade_rejects_variante_from_different_produto(): void
    {
        $this->loginUser();
        $produto1 = Produto::factory()->create(['estoque' => 10, 'estoque_compartilhado' => true]);
        $produto2 = Produto::factory()->create(['estoque' => 10, 'estoque_compartilhado' => true]);
        $grupo2   = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto2->id]);
        $val2     = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo2->id]);
        $var2     = ProdutoVariante::factory()->create(['produto_id' => $produto2->id, 'valores' => [$val2->id]]);

        // Add produto1 (no variant) to cart first
        $this->postJson('/carrinho/adicionar', ['produto_id' => $produto1->id, 'quantidade' => 1]);

        // Try to update with a variant belonging to produto2
        $response = $this->postJson('/carrinho/atualizar', [
            'produto_id'          => $produto1->id,
            'produto_variante_id' => $var2->id,
            'quantidade'          => 2,
        ]);

        $response->assertStatus(422);
    }

    public function test_remover_uses_composite_key(): void
    {
        $this->loginUser();
        $produto = Produto::factory()->create(['estoque' => 10, 'estoque_compartilhado' => true]);
        $grupo   = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id]);
        $v1      = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id, 'valor' => 'Preto']);
        $v2      = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id, 'valor' => 'Branco']);
        $var1    = ProdutoVariante::factory()->create(['produto_id' => $produto->id, 'valores' => [$v1->id]]);
        $var2    = ProdutoVariante::factory()->create(['produto_id' => $produto->id, 'valores' => [$v2->id]]);

        $this->postJson('/carrinho/adicionar', ['produto_id' => $produto->id, 'produto_variante_id' => $var1->id, 'quantidade' => 1]);
        $this->postJson('/carrinho/adicionar', ['produto_id' => $produto->id, 'produto_variante_id' => $var2->id, 'quantidade' => 1]);

        $this->postJson('/carrinho/remover', ['produto_id' => $produto->id, 'produto_variante_id' => $var1->id]);

        $carrinho = Pedido::where('user_id', auth()->id())->where('status', 'carrinho')->with('itens')->first();
        $this->assertCount(1, $carrinho->itens);
        $this->assertEquals($var2->id, $carrinho->itens->first()->produto_variante_id);
    }
}
