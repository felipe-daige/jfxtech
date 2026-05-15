<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Produto;
use App\Services\FreteEstimativaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesoDimensionalTest extends TestCase
{
    use RefreshDatabase;

    private function criarProduto(array $attrs = []): Produto
    {
        $categoria = Categoria::factory()->create();

        return Produto::factory()->create(array_merge([
            'categoria_id' => $categoria->id,
        ], $attrs));
    }

    public function test_peso_dimensional_calcula_corretamente(): void
    {
        $produto = $this->criarProduto([
            'peso'        => 0.5,
            'comprimento' => 40,
            'largura'     => 30,
            'altura'      => 20,
        ]);

        // 40 × 30 × 20 / 6000 = 4.0 kg
        $this->assertEquals(4.0, $produto->peso_dimensional);
    }

    public function test_peso_dimensional_retorna_null_sem_dimensoes(): void
    {
        $produto = $this->criarProduto([
            'peso'        => 1.0,
            'comprimento' => null,
            'largura'     => null,
            'altura'      => null,
        ]);

        $this->assertNull($produto->peso_dimensional);
    }

    public function test_peso_dimensional_retorna_null_com_dimensoes_parciais(): void
    {
        $produto = $this->criarProduto([
            'peso'        => 1.0,
            'comprimento' => 40,
            'largura'     => null,
            'altura'      => 20,
        ]);

        $this->assertNull($produto->peso_dimensional);
    }

    public function test_peso_cobranca_usa_dimensional_quando_maior(): void
    {
        $produto = $this->criarProduto([
            'peso'        => 0.5,
            'comprimento' => 40,
            'largura'     => 30,
            'altura'      => 20,
        ]);

        // peso real 0.5, dimensional 4.0 → cobrança = 4.0
        $this->assertEquals(4.0, $produto->peso_cobranca);
    }

    public function test_peso_cobranca_usa_peso_real_quando_maior(): void
    {
        $produto = $this->criarProduto([
            'peso'        => 5.0,
            'comprimento' => 10,
            'largura'     => 10,
            'altura'      => 10,
        ]);

        // dimensional = 10×10×10/6000 ≈ 0.167 kg, peso real 5.0 > dimensional
        $this->assertEquals(5.0, $produto->peso_cobranca);
    }

    public function test_peso_cobranca_sem_dimensoes_retorna_peso_real(): void
    {
        $produto = $this->criarProduto([
            'peso'        => 1.5,
            'comprimento' => null,
            'largura'     => null,
            'altura'      => null,
        ]);

        $this->assertEquals(1.5, $produto->peso_cobranca);
    }

    public function test_peso_cobranca_retorna_null_sem_peso_e_sem_dimensoes(): void
    {
        $produto = $this->criarProduto([
            'peso'        => null,
            'comprimento' => null,
            'largura'     => null,
            'altura'      => null,
        ]);

        $this->assertNull($produto->peso_cobranca);
    }

    public function test_frete_estimado_usa_peso_dimensional_quando_maior(): void
    {
        // C=40, L=30, A=20 → dimensional=4.0kg; peso real=0.5kg → cobra 4.0
        // Faixa '3-5' em outras_regioes (PAC) = R$ 55
        $produto = $this->criarProduto([
            'peso'        => 0.5,
            'comprimento' => 40,
            'largura'     => 30,
            'altura'      => 20,
        ]);

        $this->assertEquals(55.0, $produto->frete_estimado_entrega);
    }

    public function test_frete_estimado_usa_peso_real_sem_dimensoes(): void
    {
        // peso real = 0.5kg, sem dimensões → faixa '0-0.5', outras_regioes PAC = R$ 23
        $produto = $this->criarProduto([
            'peso'        => 0.5,
            'comprimento' => null,
            'largura'     => null,
            'altura'      => null,
        ]);

        $this->assertEquals(23.0, $produto->frete_estimado_entrega);
    }

    public function test_frete_estimado_retorna_null_sem_peso(): void
    {
        $produto = $this->criarProduto([
            'peso'        => null,
            'comprimento' => null,
            'largura'     => null,
            'altura'      => null,
        ]);

        $this->assertNull($produto->frete_estimado_entrega);
    }

    public function test_servico_calcula_peso_dimensional(): void
    {
        $servico = app(FreteEstimativaService::class);

        $this->assertEquals(4.0, $servico->calcularPesoDimensional(40, 30, 20));
    }

    public function test_servico_calcula_peso_cobranca_com_dimensional_maior(): void
    {
        $servico = app(FreteEstimativaService::class);

        $this->assertEquals(4.0, $servico->calcularPesoCobranca(0.5, 40, 30, 20));
    }

    public function test_servico_calcula_peso_cobranca_sem_dimensoes(): void
    {
        $servico = app(FreteEstimativaService::class);

        $this->assertEquals(2.0, $servico->calcularPesoCobranca(2.0));
    }
}
