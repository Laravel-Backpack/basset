<?php

return [
    // development mode, assets will not be internalized
    'dev_mode' => env('BASSET_DEV_MODE', env('APP_ENV') === 'local'),

    // verify ssl certificate while fetching assets
    'verify_ssl_certificate' => env('BASSET_VERIFY_SSL_CERTIFICATE', true),

    // disk and path where to store bassets
    'disk' => env('BASSET_DISK', 'basset'),

    // the path where assets will be stored inside the `public` folder
    'path' => env('BASSET_PATH', 'bassets'),

    // use cache map file (.basset)
    'cache_map' => env('BASSET_CACHE_MAP', true),

    // view paths that may use @basset
    // used to internalize assets in advance with artisan basset:internalize
    'view_paths' => [
        resource_path('views'),
    ],

    'assets' => App\Assets::class,

    // content security policy nonce
    'nonce' => null,

    // use relative path
    'relative_paths' => env('BASSET_RELATIVE_PATHS', true),
];
