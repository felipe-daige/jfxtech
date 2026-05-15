<?php

namespace App\Services;

class FreteEstimativaService
{
    private array $tabelaPAC = [
        'pr_londrina' => [
            '0-0.5' => 14.50,
            '0.5-1' => 18.00,
            '1-2'   => 22.00,
            '2-3'   => 30.00,
            '3-5'   => 40.00,
            '5-10'  => 60.00,
        ],
        'sp' => [
            '0-0.5' => 17.00,
            '0.5-1' => 21.00,
            '1-2'   => 25.00,
            '2-3'   => 34.00,
            '3-5'   => 45.00,
            '5-10'  => 65.00,
        ],
        'rj' => [
            '0-0.5' => 20.00,
            '0.5-1' => 24.00,
            '1-2'   => 29.00,
            '2-3'   => 39.00,
            '3-5'   => 50.00,
            '5-10'  => 70.00,
        ],
        'outras_regioes' => [
            '0-0.5' => 23.00,
            '0.5-1' => 28.00,
            '1-2'   => 33.00,
            '2-3'   => 44.00,
            '3-5'   => 55.00,
            '5-10'  => 75.00,
        ],
    ];

    private array $tabelaSEDEX = [
        'pr_londrina' => [
            '0-0.5' => 22.00,
            '0.5-1' => 27.00,
            '1-2'   => 32.00,
            '2-3'   => 42.00,
            '3-5'   => 55.00,
            '5-10'  => 80.00,
        ],
        'sp' => [
            '0-0.5' => 25.00,
            '0.5-1' => 30.00,
            '1-2'   => 36.00,
            '2-3'   => 46.00,
            '3-5'   => 60.00,
            '5-10'  => 85.00,
        ],
        'rj' => [
            '0-0.5' => 28.00,
            '0.5-1' => 34.00,
            '1-2'   => 40.00,
            '2-3'   => 51.00,
            '3-5'   => 65.00,
            '5-10'  => 90.00,
        ],
        'outras_regioes' => [
            '0-0.5' => 32.00,
            '0.5-1' => 38.00,
            '1-2'   => 45.00,
            '2-3'   => 57.00,
            '3-5'   => 70.00,
            '5-10'  => 95.00,
        ],
    ];

    public function calcularPorRegiao(float $peso, string $regiao = 'outras_regioes', string $modalidade = 'pac'): float
    {
        $tabela = $modalidade === 'sedex' ? $this->tabelaSEDEX : $this->tabelaPAC;
        $faixa = $this->determinarFaixaPeso($peso);

        return (float) ($tabela[$regiao][$faixa] ?? 0.0);
    }

    /**
     * Pior caso: PAC para a região mais distante (outras_regioes).
     * Usado como estimativa conservadora de custo de envio por produto.
     */
    public function calcularPiorCasoPAC(float $peso): float
    {
        return $this->calcularPorRegiao($peso, 'outras_regioes', 'pac');
    }

    public function calcularPesoDimensional(float $comprimento, float $largura, float $altura): float
    {
        return round(($comprimento * $largura * $altura) / 6000, 3);
    }

    public function calcularPesoCobranca(float $pesoReal, ?float $comprimento = null, ?float $largura = null, ?float $altura = null): float
    {
        if (!$comprimento || !$largura || !$altura) return $pesoReal;
        $pesoDimensional = $this->calcularPesoDimensional($comprimento, $largura, $altura);
        return max($pesoReal, $pesoDimensional);
    }

    public function determinarRegiao(string $cep): string
    {
        $prefixo = (int) substr($cep, 0, 2);

        if ($prefixo >= 80 && $prefixo <= 87) {
            return 'pr_londrina';
        }

        if ($prefixo >= 1 && $prefixo <= 19) {
            return 'sp';
        }

        if ($prefixo >= 20 && $prefixo <= 28) {
            return 'rj';
        }

        return 'outras_regioes';
    }

    public function determinarFaixaPeso(float $peso): string
    {
        if ($peso <= 0.5) return '0-0.5';
        if ($peso <= 1.0) return '0.5-1';
        if ($peso <= 2.0) return '1-2';
        if ($peso <= 3.0) return '2-3';
        if ($peso <= 5.0) return '3-5';
        return '5-10';
    }

    public function getTabelaPAC(): array
    {
        return $this->tabelaPAC;
    }

    public function getTabelaSEDEX(): array
    {
        return $this->tabelaSEDEX;
    }
}
