<?php

return [
    // development mode, assets will not be internalized
    'dev_mode' => env('BASSET_DEV_MODE', env('APP_ENV') === 'local'),

    // all external urls (usually cdns) will be cache on first request or 
    // when running `basset:cache` even if dev_mode is enabled
    'force_url_cache' => true,

    // verify ssl certificate while fetching assets
    'verify_ssl_certificate' => env('BASSET_VERIFY_SSL_CERTIFICATE', true),

    // disk and path where to store bassets
    'disk' => env('BASSET_DISK', 'basset'),

    // the path where assets will be stored inside the `public` folder
    // if you change this, you should also add it to your .gitignore file
    'path' => env('BASSET_PATH', 'bassets'),

    // use cache map file (.basset)
    'cache_map' => env('BASSET_CACHE_MAP', true),

    // view paths that may use @basset
    // used to internalize assets in advance with artisan basset:internalize
    'view_paths' => [
        resource_path('views'),
    ],

    // a class that allow you to define assets that can overwrite the assets on the map. 
    // packages can define the assets they need in a map, that way you can overwrite
    // the package assets without modifying the package files, eg: you want to use
    // a different version of a package asset without modifying the package views.
    // this file shouldn't be used to add new assets, but exclusively to overwrite existing ones.
    // to implement it, create a new class that implements the `Basset\AssetOverwrites` interface.
    /**
     * namespace App;
     * 
     * use Basset\AssetOverwrites;
     * 
     * class AssetOverwrites implements AssetOverwrites
     * {
     *    public function getAssets(): array
     *    {
     *      return [
     *        'some-asset-key' => 'https://some-cdn.com/some-asset@v2.3-asset.css',
     *      ];
     *    }
     * 
     * }
     */
    'asset_overwrites' => null,

    // content security policy nonce
    'nonce' => null,

    // use relative path
    'relative_paths' => env('BASSET_RELATIVE_PATHS', true),
];
