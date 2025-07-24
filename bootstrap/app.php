<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add CORS handling for API routes
        $middleware->api([
            \Illuminate\Http\Middleware\HandleCors::class,
            \Spatie\ResponseCache\Middlewares\CacheResponse::class,
        ]);

        // Add security headers middleware globally
        // $middleware->web([
        // \App\Http\Middleware\SecurityHeadersMiddleware::class,
        // ]);
    
        $middleware->web()->trustProxies(at: '*');
        $middleware->alias([
            'doNotCacheResponse' => \Spatie\ResponseCache\Middlewares\DoNotCacheResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
