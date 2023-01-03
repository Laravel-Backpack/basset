<?php

namespace DigitallyHappy\Assets;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;

class AssetsServiceProvider extends ServiceProvider
{
    protected $commands = [
        \DigitallyHappy\Assets\Console\Commands\BassetInternalize::class,
        \DigitallyHappy\Assets\Console\Commands\BassetClear::class,
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/config/digitallyhappy/assets.php' => config_path('digitallyhappy/assets.php'),
        ], 'config');

        // Registering package commands.
        if (! empty($this->commands)) {
            $this->commands($this->commands);
        }
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

        // Merge the configuration file.
        $this->mergeConfigFrom(__DIR__.'/config/digitallyhappy/assets.php', 'digitallyhappy.assets');

        // We register the Assets Facade so developer could use it in views like: Assets::isAssetLoaded($asset)
        // $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        // $loader->alias('Assets', '\DigitallyHappy\Assets\Facade\Assets');

        $this->registerBladeDirectives();
    }

    /**
     * Register Blade Directives.
     *
     * @return void
     */
    protected function registerBladeDirectives()
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $bladeCompiler->directive('loadStyleOnce', function (string $parameter): string {
                return "<?php Assets::echoCss({$parameter}); ?>";
            });

            $bladeCompiler->directive('loadScriptOnce', function (string $parameter): string {
                return "<?php Assets::echoJs({$parameter}); ?>";
            });

            $bladeCompiler->directive('loadOnce', function (string $parameter): string {
                // determine if it's a CSS or JS file
                $cleanParameter = Str::of($parameter)->trim("'")->trim('"')->trim('`');
                $filePath = Str::of($cleanParameter)->before('?')->before('#');

                // mey be useful to get the second parameter
                // if (Str::contains($parameter, ',')) {
                //     $secondParameter = Str::of($parameter)->after(',')->trim(' ');
                // }

                if (substr($filePath, -3) === '.js') {
                    return "<?php Assets::echoJs({$parameter}); ?>";
                }

                if (substr($filePath, -4) === '.css') {
                    return "<?php Assets::echoCss({$parameter}); ?>";
                }

                // it's a block start
                return "<?php if(! Assets::isLoaded('{$cleanParameter}')) { Assets::markAsLoaded('{$cleanParameter}'); ?>";
            });

            $bladeCompiler->directive('endLoadOnce', function (): string {
                return '<?php } ?>';
            });

            $bladeCompiler->directive('basset', function (string $parameter): string {
                return "<?php Assets::basset({$parameter}); ?>";
            });
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['assets'];
    }
}
