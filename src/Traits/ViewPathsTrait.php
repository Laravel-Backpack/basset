<?php

namespace Backpack\Basset\Traits;

trait ViewPathsTrait
{
    private $viewPaths = [];

    /**
     * Initialize view paths.
     *
     * @return void
     */
    private function initViewPaths(): void
    {
        $this->viewPaths = config('backpack.basset.view_paths', []);
    }

    /**
     * Add a view path that may use @basset directive
     * This is used to internalize assets in advance
     * with the command artisan basset:internalize.
     *
     * @param  string  $path
     * @return void
     */
    public function addViewPath(string $path): void
    {
        if (! in_array($path, $this->viewPaths)) {
            $this->viewPaths[] = $path;
        }
    }

    /**
     * Gets the current view paths.
     *
     * @return array
     */
    public function getViewPaths(): array
    {
        return $this->viewPaths;
    }
}
