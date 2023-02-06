<?php

return [
    'cache_cdns' => true,
    'cache_path' => storage_path('app/public/bassets'),
    'cache_public_path' => 'storage/bassets',
    'view_paths' => [
        resource_path('views'),
        base_path('vendor/backpack/crud/src/resources'),
        base_path('vendor/backpack/pro/resources'),
    ],
];
