<?php

return [
    // disk and path where to store bassets
    'disk' => 'public',
    'path' => 'bassets',

    // cachebusting string variable that is added to all bassets
    'cachebusting' => false,

    // view paths that may use @basset
    // used to internalize assets in advance with artisan basset:internalize
    'view_paths' => [
        resource_path('views'),
        base_path('vendor/backpack/crud/src/resources'),
        base_path('vendor/backpack/pro/resources'),
    ],
];
