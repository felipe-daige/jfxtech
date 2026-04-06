<?php
namespace App\Http\Middleware;

use App\Models\Affiliate;
use App\Models\AffiliateSetting;
use App\Services\AffiliateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $ref = $request->query('ref');

        if ($ref && !$request->hasCookie(AffiliateService::COOKIE_NAME)) {
            $affiliate = Affiliate::where('codigo', $ref)
                ->where('status', 'ativo')
                ->first();

            if ($affiliate) {
                $days = (int) (AffiliateSetting::where('key', 'cookie_days')->first()?->value ?? 30);
                $response = $next($request);
                $response->withCookie(cookie(AffiliateService::COOKIE_NAME, $ref, $days * 24 * 60));
                return $response;
            }
        }

        return $next($request);
    }
}
