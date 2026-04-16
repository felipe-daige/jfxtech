<?php

namespace App\Http\Middleware;

use App\Services\CouponApplicationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureCouponCode
{
    public function __construct(private CouponApplicationService $couponApplicationService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query->has('cupom')) {
            $codigo = $this->couponApplicationService->normalizeCode($request->query('cupom'));

            if ($codigo) {
                $request->session()->put(CouponApplicationService::SESSION_PENDING_COUPON, $codigo);
            } else {
                $request->session()->forget(CouponApplicationService::SESSION_PENDING_COUPON);
            }
        }

        return $next($request);
    }
}
