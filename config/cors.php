<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS')
        ? explode(',', env('CORS_ALLOWED_ORIGINS'))
        : [
            env('FRONTEND_URL', 'http://localhost:3000'),
            'http://localhost:5173',
            'http://localhost:8000',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:8000',
        ],

    'allowed_origins_patterns' => env('CORS_ALLOW_ALL_ORIGINS_WITH_CREDENTIALS', false)
        ? ['/^https?:\/\/.*$/']
        : (env('CORS_ALLOWED_ORIGINS_PATTERNS') ? explode(',', env('CORS_ALLOWED_ORIGINS_PATTERNS')) : []),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),

];
