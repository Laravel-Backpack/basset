<?php

namespace Backpack\Basset\Helpers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CacheMap
{
    private array $map = [];
    private string $basePath;
    private string $filePath;
    private FilesystemAdapter $disk;
    private bool $isActive = false;
    private bool $isDirty = false;

    public function __construct(FilesystemAdapter $disk, string $basePath)
    {
        $this->isActive = config('backpack.basset.cache_map', false);
        if (! $this->isActive) {
            return;
        }

        $this->disk = $disk;
        $this->basePath = $basePath;
        $this->filePath = $this->disk->path($this->basePath.'.basset');

        // Load map
        if (File::exists($this->filePath)) {
            $this->map = json_decode(File::get($this->filePath), true);
        }
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

        // sort the map file
        ksort($this->map);

        // save file
        File::put($this->filePath, json_encode($this->map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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

        // Clean both asset and path
        $asset = $this->normalizeAsset($asset);

        $this->map[$asset] = Str::of($path)->after($this->disk->url($this->basePath))->start('/');
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
        // Clean asset path
        $asset = $this->normalizeAsset($asset);

        if (! $this->isActive || ! ($this->map[$asset] ?? false)) {
            return false;
        }

        return $this->disk->url(rtrim($this->basePath, '/').$this->map[$asset]);
    }

    /**
     * Normalize asset path to remove unwanted system paths.
     *
     * @param  string  $asset
     * @return string
     */
    private function normalizeAsset(string $asset): string
    {
        return (string) Str::of($asset)->after(base_path())->trim('/\\');
    }
}
