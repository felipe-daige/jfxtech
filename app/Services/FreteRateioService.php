<?php

namespace App\Services;

use App\Models\ItemPedido;
use Illuminate\Support\Collection;

class FreteRateioService
{
    public function __construct(
        protected FreteEstimativaService $freteEstimativa
    ) {}

    /**
     * Calcula o frete estimado total do pedido e distribui proporcionalmente
     * pelo peso de cada item × quantidade.
     *
     * Retorna array [ item_id => frete_por_unidade ].
     * Itens sem peso retornam 0.
     */
    public function calcularRateio(Collection $itens): array
    {
        // Calcula peso total do pedido (peso do produto × quantidade)
        $pesoTotal = 0.0;
        foreach ($itens as $item) {
            $peso = (float) ($item->produto?->peso ?? 0);
            $pesoTotal += $peso * (int) $item->quantidade;
        }

        if ($pesoTotal <= 0) {
            return [];
        }

        // Frete estimado total do pedido (PAC pior caso para o peso total)
        $freteTotalPedido = $this->freteEstimativa->calcularPiorCasoPAC($pesoTotal);

        $rateio = [];
        foreach ($itens as $item) {
            $pesoProduto = (float) ($item->produto?->peso ?? 0);
            $pesoItem = $pesoProduto * (int) $item->quantidade;

            if ($pesoItem <= 0 || (int) $item->quantidade <= 0) {
                $rateio[$item->id] = 0.0;
                continue;
            }

            $freteItem = ($pesoItem / $pesoTotal) * $freteTotalPedido;
            $fretePorUnidade = $freteItem / (int) $item->quantidade;
            $rateio[$item->id] = round($fretePorUnidade, 2);
        }

        return $rateio;
    }
}
