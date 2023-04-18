<?php

namespace Backpack\Basset\Helpers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CacheMap
{
    private $map = [];
    private $path;
    private $isActive = false;
    private $isDirty = false;

    public function __construct()
    {
        $this->isActive = config('backpack.basset.cache_map', false);
        if (! $this->isActive) {
            return;
        }

        /** @var FilesystemAdapter */
        $disk = Storage::disk(config('backpack.basset.disk'));
        $this->path = $disk->path('.basset');

        // Load map
        $this->map = File::exists($this->path) ? json_decode(File::get($this->path), true) : [];
    }

    /**
     * Saves the cache map to the .basset file.
     *
     * @return void
     */
    public function save(): void
    {
        if (! $this->isDirty || ! $this->isActive) {
            return;
        }

        // save file
        File::put($this->path, json_encode($this->map, JSON_PRETTY_PRINT));
    }

    /**
     * Adds an asset to the cache map.
     *
     * @param  string  $asset
     * @param  string  $path
     * @return void
     */
    public function addAsset(string $asset, string|bool $path = true): void
    {
        if (! $this->isActive) {
            return;
        }

        $this->map[$asset] = $path;
        $this->isDirty = true;
    }

    /**
     * Gets the asset url from map.
     *
     * @param  string  $asset
     * @return string | false
     */
    public function getAsset(string $asset): string|false
    {
        if (! $this->isActive) {
            return false;
        }

        return $this->map[$asset] ?? false;
    }
}
