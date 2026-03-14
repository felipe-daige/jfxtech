<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Align PHP session GC with Laravel's SESSION_LIFETIME.
        // By default PHP uses gc_maxlifetime=1440s (24min) which can GC
        // session files before Laravel's 120min lifetime expires, causing 419.
        ini_set('session.gc_maxlifetime', config('session.lifetime') * 60);
    }
}
