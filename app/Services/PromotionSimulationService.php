<?php

namespace App\Services;

use App\Enums\PedidoStatus;
use App\Models\ItemPedido;
use App\Models\Produto;
use App\Services\FreteEstimativaService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PromotionSimulationService
{
    public function simulate(
        array $productIds,
        int $periodDays,
        float $discountPercent = 0,
        float $extraUnitCost = 0,
        float $extraOrderCost = 0
    ): array {
        $productIds = collect($productIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $products = Produto::query()
            ->select([
                'id',
                'nome',
                'marca',
                'preco',
                'preco_original',
                'custo_compra',
                'frete_compra',
                'peso',
                'desconto_percentual',
                'em_promocao',
                'ativo',
            ])
            ->whereIn('id', $productIds)
            ->orderBy('nome')
            ->get()
            ->keyBy('id');

        $resolvedIds = $products->keys()->values()->all();
        $periodStart = now()->subDays($periodDays)->startOfDay();

        $items = ItemPedido::query()
            ->with([
                'produto:id,nome,marca,preco,preco_original,custo_compra,frete_compra,peso,desconto_percentual,em_promocao,ativo',
                'produtoVariante:id,produto_id,custo_compra,frete_compra',
            ])
            ->whereIn('produto_id', $resolvedIds)
            ->whereHas('pedido', function ($query) use ($periodStart) {
                $query->whereIn('status', PedidoStatus::performanceValues())
                    ->where('created_at', '>=', $periodStart);
            })
            ->get();

        $itemsByProduct = $items->groupBy('produto_id');
        $selectedOrderCount = $items->pluck('pedido_id')->unique()->count();
        $selectedUnits = (int) $items->sum('quantidade');
        $extraOrderCostPerUnit = $selectedUnits > 0
            ? $selectedOrderCount * $extraOrderCost / $selectedUnits
            : 0.0;

        $productsData = $products->values()->map(function (Produto $product) use (
            $itemsByProduct,
            $discountPercent,
            $extraUnitCost,
            $extraOrderCostPerUnit
        ) {
            $productItems = $itemsByProduct->get($product->id, collect());

            return $this->buildProductSimulationRow(
                $product,
                $productItems,
                $discountPercent,
                $extraUnitCost,
                $extraOrderCostPerUnit
            );
        })->values();

        $currentRevenue = round((float) $productsData->sum('current_revenue_total'), 2);
        $currentCost = round((float) $productsData->sum('current_cost_total'), 2);
        $currentProfit = round($currentRevenue - $currentCost, 2);
        $simulatedRevenue = round((float) $productsData->sum('simulated_revenue_total'), 2);
        $simulatedCost = round((float) $productsData->sum('simulated_cost_total'), 2);
        $simulatedProfit = round($simulatedRevenue - $simulatedCost, 2);
        $missingCostProducts = $productsData->where('missing_cost', true)->count();
        $missingCostUnits = (int) $productsData->sum('missing_cost_units');

        return [
            'meta' => [
                'product_ids' => $resolvedIds,
                'products_count' => count($resolvedIds),
                'period_days' => $periodDays,
                'period_start' => $periodStart->toDateString(),
                'period_label' => $this->formatPeriodLabel($periodDays, $periodStart),
                'discount_percent' => round($discountPercent, 2),
                'extra_unit_cost' => round($extraUnitCost, 2),
                'extra_order_cost' => round($extraOrderCost, 2),
                'selected_units' => $selectedUnits,
                'selected_orders' => $selectedOrderCount,
                'extra_order_cost_per_unit' => round($extraOrderCostPerUnit, 4),
            ],
            'summary' => [
                'current' => [
                    'revenue_total' => $currentRevenue,
                    'cost_total' => $currentCost,
                    'profit_total' => $currentProfit,
                    'margin_percent' => $currentRevenue > 0 ? round(($currentProfit / $currentRevenue) * 100, 2) : 0.0,
                    'units' => $selectedUnits,
                ],
                'simulated' => [
                    'revenue_total' => $simulatedRevenue,
                    'cost_total' => $simulatedCost,
                    'profit_total' => $simulatedProfit,
                    'margin_percent' => $simulatedRevenue > 0 ? round(($simulatedProfit / $simulatedRevenue) * 100, 2) : 0.0,
                    'units' => $selectedUnits,
                ],
                'delta' => [
                    'revenue_total' => round($simulatedRevenue - $currentRevenue, 2),
                    'cost_total' => round($simulatedCost - $currentCost, 2),
                    'profit_total' => round($simulatedProfit - $currentProfit, 2),
                    'margin_percent' => round(
                        ($simulatedRevenue > 0 ? ($simulatedProfit / $simulatedRevenue) * 100 : 0)
                        - ($currentRevenue > 0 ? ($currentProfit / $currentRevenue) * 100 : 0),
                        2
                    ),
                ],
            ],
            'alerts' => $this->buildAlerts(
                $productsData,
                $missingCostProducts,
                $missingCostUnits,
                $currentRevenue,
                $currentProfit,
                $simulatedRevenue,
                $simulatedProfit
            ),
            'products' => $productsData->all(),
        ];
    }

    private function buildProductSimulationRow(
        Produto $product,
        Collection $items,
        float $discountPercent,
        float $extraUnitCost,
        float $extraOrderCostPerUnit
    ): array {
        $units = (int) $items->sum('quantidade');
        $orderCount = $items->pluck('pedido_id')->unique()->count();
        $currentPrice = round((float) $product->preco_com_desconto, 2);
        $simulatedPrice = round($currentPrice * (1 - ($discountPercent / 100)), 2);
        $productBaseCostTotal = 0.0;
        $missingCost = false;
        $missingCostUnits = 0;

        foreach ($items as $item) {
            $cost = $this->resolveItemCost($item);

            if ($cost === null) {
                $missingCost = true;
                $missingCostUnits += (int) $item->quantidade;
                $cost = 0.0;
            }

            $productBaseCostTotal += (int) $item->quantidade * $cost;
        }

        if ($units === 0 && $product->custo_compra === null) {
            $missingCost = true;
        }

        $averageBaseCostPerUnit = $units > 0
            ? round($productBaseCostTotal / $units, 2)
            : ($product->custo_efetivo !== null ? round($product->custo_efetivo, 2) : 0.0);

        $currentRevenueTotal = round($currentPrice * $units, 2);
        $currentCostTotal = round($units > 0 ? $productBaseCostTotal : 0.0, 2);
        $currentProfitTotal = round($currentRevenueTotal - $currentCostTotal, 2);

        $simulatedRevenueTotal = round($simulatedPrice * $units, 2);
        $simulatedCostTotal = round(
            ($units > 0 ? $productBaseCostTotal : 0.0)
            + ($units * $extraUnitCost)
            + ($units * $extraOrderCostPerUnit),
            2
        );
        $simulatedProfitTotal = round($simulatedRevenueTotal - $simulatedCostTotal, 2);

        $currentUnitProfit = round($currentPrice - $averageBaseCostPerUnit, 2);
        $simulatedUnitProfit = round($simulatedPrice - $averageBaseCostPerUnit - $extraUnitCost - $extraOrderCostPerUnit, 2);

        $currentMargin = $currentRevenueTotal > 0
            ? round(($currentProfitTotal / $currentRevenueTotal) * 100, 2)
            : ($currentPrice > 0 ? round(($currentUnitProfit / $currentPrice) * 100, 2) : 0.0);

        $simulatedMargin = $simulatedRevenueTotal > 0
            ? round(($simulatedProfitTotal / $simulatedRevenueTotal) * 100, 2)
            : ($simulatedPrice > 0 ? round(($simulatedUnitProfit / $simulatedPrice) * 100, 2) : 0.0);

        return [
            'produto_id' => $product->id,
            'nome' => $product->nome,
            'marca' => $product->marca,
            'ativo' => (bool) $product->ativo,
            'units' => $units,
            'orders' => $orderCount,
            'current_price' => $currentPrice,
            'simulated_price' => $simulatedPrice,
            'discount_percent' => round($discountPercent, 2),
            'average_base_cost_per_unit' => round($averageBaseCostPerUnit, 2),
            'extra_unit_cost' => round($extraUnitCost, 2),
            'extra_order_cost_per_unit' => round($extraOrderCostPerUnit, 4),
            'current_revenue_total' => $currentRevenueTotal,
            'current_cost_total' => $currentCostTotal,
            'current_profit_total' => $currentProfitTotal,
            'current_margin_percent' => $currentMargin,
            'current_unit_profit' => $currentUnitProfit,
            'simulated_revenue_total' => $simulatedRevenueTotal,
            'simulated_cost_total' => $simulatedCostTotal,
            'simulated_profit_total' => $simulatedProfitTotal,
            'simulated_margin_percent' => $simulatedMargin,
            'simulated_unit_profit' => $simulatedUnitProfit,
            'delta_profit_total' => round($simulatedProfitTotal - $currentProfitTotal, 2),
            'delta_margin_percent' => round($simulatedMargin - $currentMargin, 2),
            'missing_cost' => $missingCost,
            'missing_cost_units' => $missingCostUnits,
        ];
    }

    private function buildAlerts(
        Collection $products,
        int $missingCostProducts,
        int $missingCostUnits,
        float $currentRevenue,
        float $currentProfit,
        float $simulatedRevenue,
        float $simulatedProfit
    ): array {
        $alerts = [];

        if ($missingCostProducts > 0) {
            $alerts[] = [
                'level' => 'info',
                'message' => $missingCostUnits > 0
                    ? "{$missingCostProducts} produto(s) têm custo incompleto; {$missingCostUnits} unidade(s) foram calculadas com custo zero."
                    : "{$missingCostProducts} produto(s) selecionado(s) não têm custo cadastrado; as margens unitárias ficam incompletas.",
            ];
        }

        if ($currentRevenue > 0) {
            $currentMargin = round(($currentProfit / $currentRevenue) * 100, 2);

            if ($currentMargin < 0) {
                $alerts[] = ['level' => 'critical', 'message' => 'O cenário atual consolidado já está com margem negativa.'];
            } elseif ($currentMargin < 20) {
                $alerts[] = ['level' => 'warning', 'message' => 'O cenário atual consolidado está abaixo da margem-alvo de 20%.'];
            }
        }

        if ($simulatedRevenue > 0) {
            $simulatedMargin = round(($simulatedProfit / $simulatedRevenue) * 100, 2);

            if ($simulatedMargin < 0) {
                $alerts[] = ['level' => 'critical', 'message' => 'A promoção simulada leva o consolidado para margem negativa.'];
            } elseif ($simulatedMargin < 20) {
                $alerts[] = ['level' => 'warning', 'message' => 'A promoção simulada deixa o consolidado abaixo da margem-alvo de 20%.'];
            }
        }

        $negativeProducts = $products->where('simulated_margin_percent', '<', 0)->count();
        $lowMarginProducts = $products->filter(function (array $product) {
            return $product['simulated_margin_percent'] >= 0 && $product['simulated_margin_percent'] < 20;
        })->count();

        if ($negativeProducts > 0) {
            $alerts[] = ['level' => 'critical', 'message' => "{$negativeProducts} produto(s) ficariam com margem negativa no cenário simulado."];
        }

        if ($lowMarginProducts > 0) {
            $alerts[] = ['level' => 'warning', 'message' => "{$lowMarginProducts} produto(s) ficariam abaixo de 20% de margem no cenário simulado."];
        }

        return $alerts;
    }

    private function resolveItemCost(ItemPedido $item): ?float
    {
        if ($item->produtoVariante) {
            $custoCompra = $item->produtoVariante->custo_compra !== null
                ? (float) $item->produtoVariante->custo_compra
                : ($item->produto?->custo_compra !== null ? (float) $item->produto->custo_compra : null);

            if ($custoCompra === null) {
                return null;
            }

            $freteCompra = $item->produtoVariante->frete_compra !== null
                ? (float) $item->produtoVariante->frete_compra
                : (float) ($item->produto?->frete_compra ?? 0);

            $freteEntrega = $item->produto?->peso
                ? app(FreteEstimativaService::class)->calcularPiorCasoPAC((float) $item->produto->peso)
                : 0.0;

            return round($custoCompra + $freteCompra + $freteEntrega, 2);
        }

        return $item->produto?->custo_efetivo;
    }

    private function formatPeriodLabel(int $periodDays, CarbonInterface $periodStart): string
    {
        return "Últimos {$periodDays} dias desde " . $periodStart->format('d/m/Y');
    }
}
