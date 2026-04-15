<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CaptureCouponCode;

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
        ]);
        $middleware->validateCsrfTokens(except: ['logout']);
        if (env('APP_ENV') === 'testing') {
            $middleware->validateCsrfTokens(except: ['*']);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
