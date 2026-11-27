<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Sign-in codes: a few per email and per network, so nobody can flood an inbox.
        RateLimiter::for('auth-code', fn (Request $request): array => [
            Limit::perMinutes(10, 5)->by('email:'.mb_strtolower((string) $request->input('email'))),
            Limit::perMinute(20)->by('ip:'.$request->ip()),
        ]);

        RateLimiter::for('auth-verify', fn (Request $request): Limit => Limit::perMinute(10)->by('ip:'.$request->ip()));
    }
}
