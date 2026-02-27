<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FreteController extends Controller
{
    /**
     * Tabela de frete manual baseada em região e peso
     */
    private $tabelaFrete = [
        'pac' => [
            'pr_londrina' => [
                '0-0.5' => 14.50,
                '0.5-1' => 18.00,
                '1-2' => 22.00,
                '2-3' => 30.00,
                '3-5' => 40.00,
                '5-10' => 60.00
            ],
            'sp' => [
                '0-0.5' => 17.00,
                '0.5-1' => 21.00,
                '1-2' => 25.00,
                '2-3' => 34.00,
                '3-5' => 45.00,
                '5-10' => 65.00
            ],
            'rj' => [
                '0-0.5' => 20.00,
                '0.5-1' => 24.00,
                '1-2' => 29.00,
                '2-3' => 39.00,
                '3-5' => 50.00,
                '5-10' => 70.00
            ],
            'outras_regioes' => [
                '0-0.5' => 23.00,
                '0.5-1' => 28.00,
                '1-2' => 33.00,
                '2-3' => 44.00,
                '3-5' => 55.00,
                '5-10' => 75.00
            ]
        ],
        'sedex' => [
            'pr_londrina' => [
                '0-0.5' => 22.00,
                '0.5-1' => 27.00,
                '1-2' => 32.00,
                '2-3' => 42.00,
                '3-5' => 55.00,
                '5-10' => 80.00
            ],
            'sp' => [
                '0-0.5' => 25.00,
                '0.5-1' => 30.00,
                '1-2' => 36.00,
                '2-3' => 46.00,
                '3-5' => 60.00,
                '5-10' => 85.00
            ],
            'rj' => [
                '0-0.5' => 28.00,
                '0.5-1' => 34.00,
                '1-2' => 40.00,
                '2-3' => 51.00,
                '3-5' => 65.00,
                '5-10' => 90.00
            ],
            'outras_regioes' => [
                '0-0.5' => 32.00,
                '0.5-1' => 38.00,
                '1-2' => 45.00,
                '2-3' => 57.00,
                '3-5' => 70.00,
                '5-10' => 95.00
            ]
        ]
    ];

    /**
     * CEP de origem da loja (Londrina - PR)
     */
    private $cepOrigem = '86010000';

    /**
     * Calcular frete baseado no CEP de destino e peso
     */
    public function calcularFrete(Request $request)
    {
        $request->validate([
            'cep_destino' => 'required|string|size:8',
            'peso_total' => 'required|numeric|min:0.1|max:10'
        ]);

        $cepDestino = $request->cep_destino;
        $pesoTotal = $request->peso_total;

        // Determinar região de destino
        $regiao = $this->determinarRegiao($cepDestino);
        
        // Determinar faixa de peso
        $faixaPeso = $this->determinarFaixaPeso($pesoTotal);

        // Calcular valores para PAC e SEDEX
        $valores = [
            'pac' => [
                'nome' => 'PAC',
                'descricao' => 'Entrega em até 7 dias úteis',
                'valor' => $this->tabelaFrete['pac'][$regiao][$faixaPeso],
                'prazo' => '5-7 dias úteis'
            ],
            'sedex' => [
                'nome' => 'SEDEX',
                'descricao' => 'Entrega em até 3 dias úteis',
                'valor' => $this->tabelaFrete['sedex'][$regiao][$faixaPeso],
                'prazo' => '2-3 dias úteis'
            ]
        ];

        return response()->json([
            'success' => true,
            'cep_origem' => $this->cepOrigem,
            'cep_destino' => $cepDestino,
            'peso_total' => $pesoTotal,
            'regiao' => $regiao,
            'faixa_peso' => $faixaPeso,
            'opcoes' => $valores
        ]);
    }

    /**
     * Determinar região baseada no CEP
     */
    private function determinarRegiao($cep)
    {
        // Primeiros dígitos do CEP para determinar região
        $prefixo = substr($cep, 0, 2);
        
        // PR (Londrina) - CEPs 86xxx-xxx
        if ($prefixo == '86') {
            return 'pr_londrina';
        }
        
        // SP - CEPs 01xxx-xxx a 05xxx-xxx
        if (in_array($prefixo, ['01', '02', '03', '04', '05'])) {
            return 'sp';
        }
        
        // RJ - CEPs 20xxx-xxx a 28xxx-xxx
        if (in_array($prefixo, ['20', '21', '22', '23', '24', '25', '26', '27', '28'])) {
            return 'rj';
        }
        
        // Outras regiões
        return 'outras_regioes';
    }

    /**
     * Determinar faixa de peso
     */
    private function determinarFaixaPeso($peso)
    {
        if ($peso <= 0.5) {
            return '0-0.5';
        } elseif ($peso <= 1) {
            return '0.5-1';
        } elseif ($peso <= 2) {
            return '1-2';
        } elseif ($peso <= 3) {
            return '2-3';
        } elseif ($peso <= 5) {
            return '3-5';
        } else {
            return '5-10';
        }
    }

    /**
     * Calcular frete do carrinho
     */
    public function calcularFreteCarrinho(Request $request)
    {
        $request->validate([
            'cep_destino' => 'required|string|size:8'
        ]);

        // Buscar itens do carrinho
        $carrinho = \App\Models\Pedido::where('user_id', auth()->id())
            ->where('status', 'carrinho')
            ->with('itens.produto')
            ->first();

        if (!$carrinho || $carrinho->itens->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Carrinho vazio'
            ], 400);
        }

        // Calcular peso total
        $pesoTotal = 0;
        foreach ($carrinho->itens as $item) {
            $pesoProduto = $item->produto->peso ?? 0.5; // Peso padrão se não informado
            $pesoTotal += $pesoProduto * $item->quantidade;
        }

        // Calcular frete
        $resultado = $this->calcularFrete(new Request([
            'cep_destino' => $request->cep_destino,
            'peso_total' => $pesoTotal
        ]));

        return $resultado;
    }
}
