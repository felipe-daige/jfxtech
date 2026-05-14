<?php

namespace App\Services;

use App\Enums\PedidoStatus;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function eligibleOrderStatuses(): array
    {
        return PedidoStatus::performanceValues();
    }

    public function progressForUser(User $user): array
    {
        $codes = $this->couponCodesForUser($user);
        $totalSales = $codes->isEmpty()
            ? 0
            : Pedido::query()
                ->whereIn('status', $this->eligibleOrderStatuses())
                ->whereIn('cupom_codigo', $codes->all())
                ->count();

        return $this->progressForSales($totalSales);
    }

    public function progressForCouponCode(string $code): array
    {
        $totalSales = Pedido::query()
            ->whereIn('status', $this->eligibleOrderStatuses())
            ->where('cupom_codigo', $code)
            ->count();

        return $this->progressForSales($totalSales, $code);
    }

    public function progressForCouponCodes(Collection|array $codes): Collection
    {
        $codes = collect($codes)
            ->filter(fn($code) => trim((string) $code) !== '')
            ->map(fn($code) => (string) $code)
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return collect();
        }

        $salesByCode = Pedido::query()
            ->select('cupom_codigo', DB::raw('COUNT(*) as total_sales'))
            ->whereIn('status', $this->eligibleOrderStatuses())
            ->whereIn('cupom_codigo', $codes->all())
            ->groupBy('cupom_codigo')
            ->pluck('total_sales', 'cupom_codigo');

        return $codes->mapWithKeys(fn($code) => [
            $code => $this->progressForSales((int) ($salesByCode[$code] ?? 0), $code),
        ]);
    }

    private function progressForSales(int $totalSales, ?string $code = null): array
    {
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

        $progressPct = $this->progressPercentage($totalSales, $currentTier);

        return [
            'coupon_code' => $code,
            'total_sales' => $totalSales,
            'current_tier' => $currentTier,
            'current_rate' => $currentTier['rate'],
            'current_label' => $currentTier['label'],
            'next_threshold' => $nextThreshold,
            'sales_to_next' => $nextThreshold === null ? 0 : max(0, $nextThreshold - $totalSales),
            'progress_pct' => $progressPct,
            'tiers' => $tiers,
        ];
    }

    private function progressPercentage(int $totalSales, array $tier): int
    {
        if ($tier['max'] === null) {
            return 100;
        }

        $range = $tier['max'] - $tier['min'];

        if ($range <= 0) {
            return 100;
        }

        return min(100, max(0, (int) round(($totalSales - $tier['min']) / $range * 100)));
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
