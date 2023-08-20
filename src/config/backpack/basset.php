<?php

return [
    // development mode, assets will not be cached
    'dev_mode' => env('BASSET_DEV_MODE', env('APP_ENV') === 'local'),

    // disk and path where to store bassets
    'disk' => env('BASSET_DISK', 'public'),
    'path' => 'basset',

    // use cache map file (.basset)
    'cache_map' => env('BASSET_CACHE_MAP', true),

    // view paths that may use @basset
    // used to cache assets in advance with artisan basset:cache
    'view_paths' => [
        resource_path('views'),
    ],

    // content security policy nonce
    'nonce' => null,

    // use relative path
    'relative_paths' => env('BASSET_RELATIVE_PATHS', true),
];
