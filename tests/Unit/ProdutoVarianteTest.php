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

    public function test_descricao_efetiva_retorna_propria_quando_preenchida(): void
    {
        $produto = Produto::factory()->create(['descricao' => 'Descrição do produto pai']);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'descricao' => 'Descrição própria da variante',
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals('Descrição própria da variante', $variante->descricao_efetiva);
    }

    public function test_descricao_efetiva_faz_fallback_para_produto_quando_null(): void
    {
        $produto = Produto::factory()->create(['descricao' => 'Descrição do produto pai']);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'descricao' => null,
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals('Descrição do produto pai', $variante->descricao_efetiva);
    }

    public function test_specs_efetivos_retorna_proprios_quando_preenchidos(): void
    {
        $produto = Produto::factory()->create(['specs' => ['cor' => 'azul']]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'specs' => ['cor' => 'vermelho', 'tamanho' => 'M'],
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals(['cor' => 'vermelho', 'tamanho' => 'M'], $variante->specs_efetivos);
    }

    public function test_specs_efetivos_faz_fallback_para_produto_quando_null(): void
    {
        $produto = Produto::factory()->create(['specs' => ['cor' => 'azul']]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'specs' => null,
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals(['cor' => 'azul'], $variante->specs_efetivos);
    }

    public function test_specs_efetivos_retorna_null_quando_ambos_sao_null(): void
    {
        $produto = Produto::factory()->create(['specs' => null]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'specs' => null,
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertNull($variante->specs_efetivos);
    }
}
