<?php

namespace DigitallyHappy\Assets;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;

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

        $this->registerBladeDirectives();
    }

    protected function registerBladeDirectives()
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $bladeCompiler->directive('loadStyleOnce', function ($parameter) {
                return "<?php Assets::echoCss({$parameter}); ?>";
            });

            $bladeCompiler->directive('loadScriptOnce', function ($parameter) {
                return "<?php Assets::echoJs({$parameter}); ?>";
            });

            $bladeCompiler->directive('loadOnce', function ($parameter) {
                // determine if it's a CSS or JS file
                $cleanParameter = Str::of($parameter)->trim("'")->trim('"')->trim('`');
                $filePath = Str::of($cleanParameter)->before('?')->before('#');

                // mey be useful to get the second parameter
                // if (Str::contains($parameter, ',')) {
                //     $secondParameter = Str::of($parameter)->after(',')->trim(' ');
                // }

                if (substr($filePath, -3) == '.js') {
                    return "<?php Assets::echoJs({$parameter}); ?>";
                }

                if (substr($filePath, -4) == '.css') {
                    return "<?php Assets::echoCss({$parameter}); ?>";
                }

                // it's a block start
                return "<?php if(! Assets::isLoaded('".$cleanParameter."')) { Assets::markAsLoaded('".$cleanParameter."');  ?>";
            });

            $bladeCompiler->directive('endLoadOnce', function () {
                return '<?php } ?>';
            });
        });
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
