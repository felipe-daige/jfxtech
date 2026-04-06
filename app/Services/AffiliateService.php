<?php
namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Models\AffiliateSetting;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AffiliateService
{
    public const COOKIE_NAME = 'affiliate_ref';

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = AffiliateSetting::where('key', $key)->first();
        return $setting?->value ?? $default;
    }

    public function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Affiliate::where('codigo', $code)->exists());

        return $code;
    }

    public function calculateCommission(Affiliate $affiliate, Pedido $pedido): float
    {
        $type  = $affiliate->commission_type ?? 'percent';
        $value = $affiliate->commission_value !== null
            ? (float) $affiliate->commission_value
            : (float) $this->getSetting('commission_percent_default', '5.00');

        return $type === 'fixed'
            ? $value
            : round((float) $pedido->valor_total * $value / 100, 2);
    }
}
