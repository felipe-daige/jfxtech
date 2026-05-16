<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProdutoPrecoCartaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_preco_cartao_adds_five_percent_to_preco_com_desconto(): void
    {
        $produto = Produto::factory()->create([
            'preco' => 100.00,
            'em_promocao' => false,
            'desconto_pix' => 5.0,
        ]);

        $this->assertEquals(105.00, $produto->preco_cartao);
    }

    public function test_preco_cartao_uses_preco_com_desconto_as_base_when_em_promocao(): void
    {
        // preco_com_desconto = preco_original * (1 - desconto_percentual/100) = 100 * 0.80 = 80
        // preco_cartao = 80 * 1.05 = 84.00
        $produto = Produto::factory()->create([
            'preco' => 80.00,
            'preco_original' => 100.00,
            'desconto_percentual' => 20.0,
            'em_promocao' => true,
            'desconto_pix' => 5.0,
        ]);

        $this->assertEquals(84.00, $produto->preco_cartao);
    }

    public function test_preco_cartao_uses_configured_desconto_pix_when_field_is_null(): void
    {
        // desconto_pix = null → getDescontoPix() returns 5.0 (default)
        $produto = Produto::factory()->create([
            'preco' => 200.00,
            'em_promocao' => false,
            'desconto_pix' => null,
        ]);

        $this->assertEquals(210.00, $produto->preco_cartao);
    }
}
