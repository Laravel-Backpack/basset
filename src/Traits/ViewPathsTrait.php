<?php

namespace Backpack\Basset\Traits;

trait ViewPathsTrait
{
    private static $viewPaths = [];

    /**
     * Initialize view paths.
     *
     * @return void
     */
    private static function initViewPaths(): void
    {
        self::$viewPaths = config('backpack.basset.view_paths', []);
    }

    /**
     * Add a view path that may use @basset directive
     * This is used to internalize assets in advance
     * with the command artisan basset:internalize.
     *
     * @param  string  $path
     * @return void
     */
    public static function addViewPath(string $path): void
    {
        if (! in_array($path, self::$viewPaths)) {
            self::$viewPaths[] = $path;
        }
    }

    /**
     * Gets the current view paths.
     *
     * @return array
     */
    public static function getViewPaths(): array
    {
        return self::$viewPaths;
    }
}
