<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage disk for product photos
    |--------------------------------------------------------------------------
    | Any disk configured in config/filesystems.php. In development use "public"
    | (served via storage symlink); in production point to "s3" backed by
    | Cloudflare R2 (same driver, just different credentials in .env).
    */
    'disk' => env('PRODUCT_PHOTOS_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | QR upload token time-to-live (minutes)
    |--------------------------------------------------------------------------
    */
    'token_ttl_minutes' => (int) env('PRODUCT_PHOTO_TOKEN_TTL_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Public base URL for the mobile upload screen
    |--------------------------------------------------------------------------
    | Base URL of the Angular app reachable from a phone (NOT localhost in dev:
    | use the LAN IP or a tunnel). The QR encodes "<base>/u/foto/{token}".
    */
    'public_base_url' => env('PRODUCT_PHOTO_PUBLIC_BASE_URL', env('APP_URL', 'http://localhost:4200')),

];
