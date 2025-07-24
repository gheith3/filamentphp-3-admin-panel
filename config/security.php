<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS (Cross-Origin Resource Sharing) Settings
    |--------------------------------------------------------------------------
    |
    | Configure CORS settings for API endpoints and cross-origin requests.
    |
    */

    'cors' => [
        'allowed_origins' => env('APP_ENV') === 'production'
            ? explode(',', env('CORS_ALLOWED_ORIGINS', ''))
            : ['*'], // Development allows all origins
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => [
            'Accept',
            'Authorization',
            'Content-Type',
            'X-Requested-With',
            'X-CSRF-TOKEN',
            'X-XSRF-TOKEN',
        ],
        'exposed_headers' => [
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
            'X-RateLimit-Reset',
        ],
        'max_age' => env('CORS_MAX_AGE', 3600),
        'supports_credentials' => env('APP_ENV') === 'production',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    |
    | Configure Content Security Policy headers for enhanced security.
    |
    */

    'csp' => [
        'enabled' => env('CSP_ENABLED', false),
        'report_only' => env('CSP_REPORT_ONLY', true),
        'report_uri' => env('CSP_REPORT_URI', '/csp-report'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for different types of requests.
    |
    */

    'rate_limiting' => [
        'enabled' => true,
        'api_general' => [
            'limit' => env('THROTTLE_REQUESTS', 60),
            'window' => env('THROTTLE_DECAY_MINUTES', 1) * 60, // convert to seconds
        ],
        'auth_attempts' => [
            'limit' => 5,
            'window' => 300, // 5 minutes
        ],
        'password_resets' => [
            'limit' => 5,
            'window' => 3600, // 1 hour
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
        'max_string_length' => 255,
        'max_text_length' => 65535,
        'max_file_size' => '10M',
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
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
        'secure_cookies' => env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production'),
        'http_only' => true,
        'same_site' => 'lax',
        'encrypt' => env('SESSION_ENCRYPT', env('APP_ENV') === 'production'),
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
        'max_file_size' => '10M',
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'scan_for_viruses' => env('VIRUS_SCAN_ENABLED', false),
        'quarantine_suspicious' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Additional security headers to be sent with responses.
    |
    */

    'headers' => [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'hsts' => [
            'enabled' => env('APP_ENV') === 'production',
            'max_age' => 31536000, // 1 year
            'include_subdomains' => true,
            'preload' => true,
        ],
    ],

];