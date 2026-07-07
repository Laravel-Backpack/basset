<?php

return [
    // when dev mode is enabled basset will replace the changed assets by either checking if the url changed
    // or comparing the file hash with the one in the asset map
    'dev_mode' => env('BASSET_DEV_MODE', env('APP_ENV') === 'local'),

    // verify ssl certificate while fetching assets
    'verify_ssl_certificate' => env('BASSET_VERIFY_SSL_CERTIFICATE', true),

    // maximum time (in seconds) to wait for a response when fetching an asset
    'fetch_timeout' => env('BASSET_FETCH_TIMEOUT', 30),

    // total number of HTTP attempts (initial + retries) when fetching an asset
    'fetch_retries' => env('BASSET_FETCH_RETRIES', 3),

    // delay (in milliseconds) between each retry attempt
    'fetch_retry_delay' => env('BASSET_FETCH_RETRY_DELAY', 10000),

    // disk and path where to store bassets
    'disk' => env('BASSET_DISK', 'basset'),

    // the path where assets will be stored inside the `public` folder
    // if you change this, you should also add it to your .gitignore file
    'path' => env('BASSET_PATH', 'basset'),

    // use cache map file (.basset).
    'cache_map' => env('BASSET_CACHE_MAP', true),

    // when enabled, basset:cache will skip re-downloading assets that are
    // already cached (same URL in cache map + file exists on disk).
    // set to true once you've verified your CDN URLs are properly versioned.
    'cache_map_comparison' => env('BASSET_CACHE_MAP_COMPARISON', false),

    // view paths that may use @basset
    // used to internalize assets in advance with artisan basset:internalize
    'view_paths' => [
        resource_path('views'),
    ],

    // define the final asset names and URLs in the asset map; this allows you to override
    // anything added to the asset map by Composer packages; this config expects
    // a class that implements `Backpack\Basset\OverridesAssets
    /**
     * namespace App;.
     *
     * use Backpack\Basset\OverridesAssets;
     *
     * class OverrideAssets implements OverridesAssets
     * {
     *    public function assets(): void
     *    {
     *      Basset::map('some-asset-key', 'asset/source/asset.js', ['integrity' => 'sha384-...']);
     *    }
     *
     * }
     */
    'asset_overrides' => null,

    // content security policy nonce
    'nonce' => null,

    // use relative path
    'relative_paths' => env('BASSET_RELATIVE_PATHS', true),

    // Extra attributes to add to every <script> tag output by Basset.
    // Useful for opt-out directives on CDN/proxy layers that rewrite scripts.
    // Example (Cloudflare Rocket Loader): ['data-cfasync' => 'false']
    'script_attributes' => [],
];
