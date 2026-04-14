<?php

namespace App\Services;

use App\Enums\PedidoStatus;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Collection;

class CouponPartnerProgressService
{
    public function tiers(): array
    {
        return [
            ['min' => 0, 'max' => 14, 'rate' => 5, 'label' => '0-14 vendas'],
            ['min' => 15, 'max' => 29, 'rate' => 6, 'label' => '15-29 vendas'],
            ['min' => 30, 'max' => 59, 'rate' => 7, 'label' => '30-59 vendas'],
            ['min' => 60, 'max' => null, 'rate' => 8, 'label' => '60+ vendas'],
        ];
    }

    public function progressForUser(User $user): array
    {
        $codes = $this->couponCodesForUser($user);
        $totalSales = $codes->isEmpty()
            ? 0
            : Pedido::query()
                ->where('status', PedidoStatus::PAGO)
                ->whereIn('cupom_codigo', $codes->all())
                ->count();

        $tiers = $this->tiers();
        $currentTier = $tiers[0];
        $nextThreshold = null;

        foreach ($tiers as $index => $tier) {
            $max = $tier['max'];

            if ($totalSales >= $tier['min'] && ($max === null || $totalSales <= $max)) {
                $currentTier = $tier;
                $nextTier = $tiers[$index + 1] ?? null;
                $nextThreshold = $nextTier['min'] ?? null;
                break;
            }
        }

        return [
            'total_sales' => $totalSales,
            'current_rate' => $currentTier['rate'],
            'current_label' => $currentTier['label'],
            'next_threshold' => $nextThreshold,
            'sales_to_next' => $nextThreshold === null ? 0 : max(0, $nextThreshold - $totalSales),
            'tiers' => $tiers,
        ];
    }

    public function couponCodesForUser(User $user): Collection
    {
        return $user->cupons()
            ->orderBy('codigo')
            ->pluck('codigo')
            ->filter()
            ->values();
    }
}
