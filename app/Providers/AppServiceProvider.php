<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Illuminate\Support\Facades\Route;

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
        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/vendor/livewire/livewire.js', $handle);
        });

        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for the application.
     */
    public function configureRateLimiting(): void
    {
        // Standard API rate limiter
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Auth-based rate limiter for authenticated users
        RateLimiter::for('auth', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(100)->by($request->user()->id)
                : Limit::perMinute(20)->by($request->ip());
        });

        // Admin panel rate limiter
        RateLimiter::for('admin', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(200)->by($request->user()->id)
                : Limit::perMinute(10)->by($request->ip());
        });

        // File upload rate limiter
        RateLimiter::for('uploads', function (Request $request) {
            return $request->user()
                ? Limit::perHour(50)->by($request->user()->id)
                : Limit::perHour(5)->by($request->ip());
        });
    }
}
