<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\CaptureCouponCode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->appendToGroup('web', [
            CaptureCouponCode::class,
        ]);
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'admin' => AdminAuth::class,
        ]);
        $middleware->validateCsrfTokens(except: ['logout']);
        if (env('APP_ENV') === 'testing') {
            $middleware->validateCsrfTokens(except: ['*']);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, \Throwable $e, Request $request) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            $appRoot = url('/');
            $requestRoot = $request->getSchemeAndHttpHost();
            $fallbackUrl = $requestRoot ?: $appRoot;
            $returnUrl = $request->headers->get('referer') ?: url()->previous() ?: $fallbackUrl;

            if (str_starts_with($returnUrl, '/')) {
                $returnUrl = rtrim($fallbackUrl, '/').$returnUrl;
            }

            $originFrom = function (string $url): ?string {
                $parts = parse_url($url);
                if (! isset($parts['scheme'], $parts['host'])) {
                    return null;
                }

                return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
            };
            $returnOrigin = $originFrom($returnUrl);
            $allowedOrigins = array_filter([
                $originFrom($appRoot),
                $requestRoot ? $originFrom($requestRoot) : null,
            ]);

            if (! $returnOrigin || ! in_array($returnOrigin, $allowedOrigins, true)) {
                $returnUrl = $fallbackUrl;
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Sua sessão expirou. A página será atualizada automaticamente.',
                    'redirect_url' => $returnUrl,
                    'reload' => true,
                ], 419)->withHeaders([
                    'X-CSRF-Expired' => '1',
                    'X-CSRF-Redirect' => $returnUrl,
                ]);
            }

            return redirect()
                ->to($returnUrl)
                ->with('csrf_token_refreshed', true)
                ->with('csrf_return_to', $returnUrl);
        });
    })->create();
