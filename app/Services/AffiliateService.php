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

    public function recordReferralOnRegister(User $user): void
    {
        $codigo = request()->cookie(self::COOKIE_NAME);
        if (!$codigo) {
            return;
        }

        $affiliate = Affiliate::where('codigo', $codigo)
            ->where('status', 'ativo')
            ->first();

        if (!$affiliate) {
            return;
        }

        // Anti-self-referral
        if ($affiliate->user_id === $user->id) {
            return;
        }

        // Skip if user already has a referral (UNIQUE constraint guard)
        if (AffiliateReferral::where('referred_user_id', $user->id)->exists()) {
            return;
        }

        AffiliateReferral::create([
            'affiliate_id'     => $affiliate->id,
            'referred_user_id' => $user->id,
            'status'           => 'pendente',
        ]);
    }

    public function handleOrderPaid(Pedido $pedido): void
    {
        if ($pedido->user_id === null) {
            return;
        }

        $referral = AffiliateReferral::where('referred_user_id', $pedido->user_id)
            ->where('status', 'pendente')
            ->first();

        if (!$referral) {
            return;
        }

        // Only commission on the very first paid order
        $paidCount = Pedido::where('user_id', $pedido->user_id)
            ->where('status', 'pago')
            ->count();

        if ($paidCount !== 1) {
            return;
        }

        $affiliate = $referral->affiliate;
        $valor = $this->calculateCommission($affiliate, $pedido);
        $graceDays = (int) $this->getSetting('grace_period_days', '30');

        AffiliateCommission::create([
            'affiliate_id' => $affiliate->id,
            'referral_id'  => $referral->id,
            'pedido_id'    => $pedido->id,
            'valor'        => $valor,
            'status'       => 'pendente',
            'eligible_at'  => now()->addDays($graceDays),
        ]);

        $referral->update([
            'status'       => 'convertido',
            'converted_at' => now(),
        ]);
    }
}
