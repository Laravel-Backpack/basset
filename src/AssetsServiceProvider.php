<?php

namespace DigitallyHappy\Assets;

use Illuminate\Support\ServiceProvider;

class AssetsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the service the package provides.
        $this->app->singleton('assets', function ($app) {
            return new AssetManager();
        });

        //we register the Assets Facade so developer could use it in views like: Assets::isAssetLoaded($asset)
        // $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        // $loader->alias('Assets', '\DigitallyHappy\Assets\Facade\Assets');

        require_once __DIR__.'/blade_directives.php';
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['assets'];
    }
}
