<?php

return [

    'paths' => ['api/*', 'auth/*', 'admin/*', 'superadmin/*', 'tpv/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:4200'),
        env('FRONTEND_URL_LOCAL'),
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
