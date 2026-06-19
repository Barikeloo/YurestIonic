<?php

return [

    'paths' => ['api/*', 'auth/*', 'admin/*', 'superadmin/*', 'tpv/*', 'public/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:4200'),
        env('FRONTEND_URL_LOCAL'),
        env('GUEST_APP_URL'),
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Accept', 'X-Guest-Session', 'Authorization'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
