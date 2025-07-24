<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security headers for production deployment to enhance
    | application security and prevent common web vulnerabilities.
    |
    */

    'headers' => [
        'hsts' => [
            'enabled' => env('FORCE_HTTPS', false),
            'max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => true,
            'preload' => true,
        ],

        'csp' => [
            'enabled' => env('CSP_ENABLED', true),
            'policy' => env(
                'CONTENT_SECURITY_POLICY',
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
                "style-src 'self' 'unsafe-inline'; " .
                "img-src 'self' data: https:; " .
                "font-src 'self' data:; " .
                "connect-src 'self'; " .
                "media-src 'self'; " .
                "object-src 'none'; " .
                "frame-ancestors 'none';"
            ),
        ],

        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'geolocation=(), microphone=(), camera=()',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Enhanced rate limiting settings for production security.
    |
    */

    'rate_limiting' => [
        'enabled' => true,
        'game_operations' => [
            'limit' => env('GAME_RATE_LIMIT', 1),
            'window' => 60, // seconds
        ],
        'leaderboard_access' => [
            'limit' => env('LEADERBOARD_RATE_LIMIT', 10),
            'window' => 60, // seconds
        ],
        'api_general' => [
            'limit' => env('THROTTLE_REQUESTS', 60),
            'window' => env('THROTTLE_DECAY_MINUTES', 1) * 60, // convert to seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation & Sanitization
    |--------------------------------------------------------------------------
    |
    | Additional security measures for input handling.
    |
    */

    'validation' => [
        'max_name_length' => 50,
        'max_phone_length' => 20,
        'min_score' => -999,
        'max_score' => 999,
        'allowed_phone_patterns' => [
            '/^\+?[1-9]\d{1,14}$/', // E.164 format
            '/^[0-9\-\+\(\)\s]{8,20}$/', // Common formats
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Enhanced session security settings for production.
    |
    */

    'session' => [
        'secure_cookies' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => true,
        'same_site' => 'lax',
        'encrypt' => env('SESSION_ENCRYPT', true),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'regenerate_on_login' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Security
    |--------------------------------------------------------------------------
    |
    | Database security and performance settings.
    |
    */

    'database' => [
        'log_queries' => env('DB_LOG_QUERIES', false),
        'slow_query_threshold' => 2000, // milliseconds
        'connection_timeout' => 60,
        'read_timeout' => 60,
        'write_timeout' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | File upload restrictions and security measures.
    |
    */

    'uploads' => [
        'max_file_size' => '2M',
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
        'scan_for_viruses' => env('VIRUS_SCAN_ENABLED', false),
        'quarantine_suspicious' => true,
    ],

];