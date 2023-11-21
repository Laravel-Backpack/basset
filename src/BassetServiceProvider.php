<?php

namespace Backpack\Basset;

use Backpack\Basset\Facades\Basset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;

/**
 * Basset Service Provider.
 *
 * @property object $app
 */
class BassetServiceProvider extends ServiceProvider
{
    protected $commands = [
        \Backpack\Basset\Console\Commands\BassetCache::class,
        \Backpack\Basset\Console\Commands\BassetClear::class,
        \Backpack\Basset\Console\Commands\BassetCheck::class,
        \Backpack\Basset\Console\Commands\BassetInstall::class,
        \Backpack\Basset\Console\Commands\BassetInternalize::class,
        \Backpack\Basset\Console\Commands\BassetFresh::class,
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

        // Load basset disk
        $this->loadDisk();

        // Run the terminate commands
        $this->app->terminating(fn () => $this->terminate());
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
            __DIR__.'/config/backpack/basset.php' => config_path('backpack/basset.php'),
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
        $this->app->scoped('basset', fn () => new BassetManager());

        // Merge the configuration file.
        $this->mergeConfigFrom(__DIR__.'/config/backpack/basset.php', 'backpack.basset');

        // Register blade directives
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
            // Basset
            $bladeCompiler->directive('basset', function (string $parameter): string {
                return "<?php Basset::basset({$parameter}); ?>";
            });

            // Basset Directory
            $bladeCompiler->directive('bassetDirectory', function (string $parameter): string {
                return "<?php Basset::bassetDirectory({$parameter}); ?>";
            });

            // Basset Archive
            $bladeCompiler->directive('bassetArchive', function (string $parameter): string {
                return "<?php Basset::bassetArchive({$parameter}); ?>";
            });

            // Basset Code Block
            $bladeCompiler->directive('bassetBlock', function (string $parameter): string {
                return "<?php \$bassetBlock = {$parameter}; ob_start(); ?>";
            });

            $bladeCompiler->directive('endBassetBlock', function (): string {
                return '<?php Basset::bassetBlock($bassetBlock, ob_get_clean()); ?>';
            });

            // Load Once
            $bladeCompiler->directive('loadOnce', function (string $parameter): string {
                // determine if it's a CSS or JS file
                $cleanParameter = Str::of($parameter)->trim("'")->trim('"')->trim('`');
                $filePath = Str::of($cleanParameter)->before('?')->before('#');

                if (substr($filePath, -3) === '.js') {
                    return "<?php Basset::echoJs({$parameter}); ?>";
                }

                if (substr($filePath, -4) === '.css') {
                    return "<?php Basset::echoCss({$parameter}); ?>";
                }

                // it's a block start
                return "<?php if(! Basset::isLoaded('{$cleanParameter}')) { Basset::markAsLoaded('{$cleanParameter}'); ?>";
            });

            $bladeCompiler->directive('endLoadOnce', function (): string {
                return '<?php } ?>';
            });

            $bladeCompiler->directive('loadStyleOnce', function (string $parameter): string {
                return "<?php Basset::echoCss({$parameter}); ?>";
            });

            $bladeCompiler->directive('loadScriptOnce', function (string $parameter): string {
                return "<?php Basset::echoJs({$parameter}); ?>";
            });
        });
    }

    /**
     * On terminate callback.
     *
     * @return void
     */
    public function terminate(): void
    {
        /** @var BassetManager */
        $basset = app('basset');

        // Log execution time
        if (config('backpack.basset.log_execution_time', false)) {
            $totalCalls = $basset->loader->getTotalCalls();
            $loadingTime = $basset->loader->getLoadingTime();

            Log::info("Basset run $totalCalls times, with an exeuction time of $loadingTime");
        }

        // Save the cache map
        $basset->cacheMap->save();
    }

    /**
     * Loads needed basset disks.
     *
     * @return void
     */
    public function loadDisk(): void
    {
        // if the basset disk already exists, don't override it
        if (app()->config['filesystems.disks.basset']) {
            return;
        }

        // if the basset disk isn't being used at all, don't even bother to add it
        if (app()->config['backpack.basset.disk'] !== 'basset') {
            return;
        }

        // add the basset disk to filesystem configuration
        // should be kept up to date with https://github.com/laravel/laravel/blob/10.x/config/filesystems.php#L39-L45
        app()->config['filesystems.disks.basset'] = [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ];
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['basset'];
    }
}
