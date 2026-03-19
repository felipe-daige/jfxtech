<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ProdutoVariante;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProdutoVarianteTest extends TestCase
{
    use RefreshDatabase;

    public function test_preco_efetivo_returns_own_preco_when_set(): void
    {
        $produto = Produto::factory()->create(['preco' => 100.00, 'em_promocao' => false]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'preco' => 149.90,
            'valores' => [1],
        ]);

        $this->assertEquals(149.90, $variante->preco_efetivo);
    }

    public function test_preco_efetivo_inherits_produto_preco_com_desconto_when_null(): void
    {
        $produto = Produto::factory()->create([
            'preco' => 80.00,
            'preco_original' => 100.00,
            'em_promocao' => true,
            'desconto_percentual' => 20.00,
        ]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'preco' => null,
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals(round($produto->preco_com_desconto, 2), $variante->preco_efetivo);
    }

    public function test_estoque_efetivo_returns_produto_estoque_when_compartilhado(): void
    {
        $produto = Produto::factory()->create(['estoque' => 50, 'estoque_compartilhado' => true]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'estoque' => null,
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals(50, $variante->estoque_efetivo);
    }

    public function test_estoque_efetivo_returns_zero_when_not_compartilhado_and_estoque_null(): void
    {
        $produto = Produto::factory()->create(['estoque' => 50, 'estoque_compartilhado' => false]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'estoque' => null,
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals(0, $variante->estoque_efetivo);
    }

    public function test_estoque_efetivo_returns_own_estoque_when_not_compartilhado(): void
    {
        $produto = Produto::factory()->create(['estoque' => 50, 'estoque_compartilhado' => false]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'estoque' => 5,
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals(5, $variante->estoque_efetivo);
    }
}
