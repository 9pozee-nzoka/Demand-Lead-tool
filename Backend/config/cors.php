<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    | Allowed origins must be set explicitly per environment via
    | CORS_ALLOWED_ORIGINS (comma-separated list).
    |
    | Dev default: http://localhost:4200 (Angular dev server)
    | Production: https://app.demandlead.io (set in .env)
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Explicit method list — no wildcard in production
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:4200')),

    'allowed_origins_patterns' => [],

    // Explicit header list — restricts what clients can send
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],

    // Cache preflight for 2 hours
    'max_age' => 7200,

    'supports_credentials' => true,
];
