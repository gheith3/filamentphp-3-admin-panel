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

    public function configureRateLimiting()
    {
         // Configure rate limiter for game operations (store-player and submit-score)
         RateLimiter::for('game-operations', function (Request $request) {
            // Identify user by phone number (from request body) or IP address as fallback
            // $identifier = $request->input('phone') ?: $request->ip();
            $identifier = $request->ip();
            
            // Allow 1 request per minute per user/phone number
            return Limit::perMinute(1)
                ->by('game-ops:' . $identifier)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many requests. Please wait 1 minute before trying again.',
                        'errors' => [
                            'rate_limit' => ['You can only perform this action once per minute.']
                        ],
                        'retry_after' => 60
                    ], 429, $headers);
                });
        });

        // Configure leaderboard rate limiter (more generous for read-only data)
        RateLimiter::for('leaderboard', function (Request $request) {
            return Limit::perMinute(10)
                ->by('leaderboard:' . $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many leaderboard requests. Please wait a moment before refreshing again.',
                        'errors' => [
                            'rate_limit' => ['You can refresh the leaderboard up to 10 times per minute.']
                        ],
                        'retry_after' => 60
                    ], 429, $headers);
                });
        });

        // Configure general API rate limiter (for all other endpoints)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
