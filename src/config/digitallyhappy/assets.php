<?php

return [
    // cache assets
    'cache' => true,

    // disk and path where to store bassets
    'disk' => 'public',
    'path' => 'bassets',

    // view paths that may use @basset
    // used to internalize assets in advance with artisan basset:internalize
    'view_paths' => [
        resource_path('views'),
        base_path('vendor/backpack/crud/src/resources'),
        base_path('vendor/backpack/pro/resources'),
    ],
];
