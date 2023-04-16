<?php

namespace Backpack\Basset\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CacheMap
{
    private $map = [];
    private $path;
    private $active = false;
    private $dirty = false;

    public function __construct()
    {
        $this->active = config('backpack.basset.cache_map', false);
        $this->path = Storage::disk(config('backpack.basset.disk'))->path('.basset');

        if (! $this->active) {
            return;
        }

        // Load map
        $this->map = File::exists($this->path) ? json_decode(File::get($this->path), true) : [];
    }

    /**
     * Saves the cache map to the .basset file
     *
     * @return void
     */
    public function save(): void
    {
        if (! $this->dirty || ! $this->active) {
            return;
        }

        // save file
        File::put($this->path, json_encode($this->map, JSON_PRETTY_PRINT));
    }

    /**
     * Adds an asset to the cache map
     *
     * @param string $asset
     * @param string $path
     * @return void
     */
    public function add(string $asset, string | bool $path = true): void
    {
        if (! $this->active) {
            return;
        }

        $this->map[$asset] = $path;
        $this->dirty = true;
    }

    /**
     * Gets the asset url from map
     *
     * @param string $asset
     * @return string | false
     */
    public function get(string $asset): string | false
    {
        if (! $this->active) {
            return false;
        }

        return $this->map[$asset] ?? false;
    }
}
