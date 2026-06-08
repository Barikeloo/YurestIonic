<?php

return [

    'disk' => env('PRODUCT_PHOTOS_DISK', 'public'),

    'token_ttl_minutes' => (int) env('PRODUCT_PHOTO_TOKEN_TTL_MINUTES', 10),

    'public_base_url' => env('PRODUCT_PHOTO_PUBLIC_BASE_URL', env('APP_URL', 'http://localhost:4200')),

];
