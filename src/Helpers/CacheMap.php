<?php

namespace Backpack\Basset\Helpers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;

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

        // save file
        File::put($this->filePath, json_encode($this->map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Adds an asset to the cache map.
     *
     * @return void
     */
    public function addAsset(CacheEntry $asset): void
    {
        if (! $this->isActive) {
            return;
        }

        $this->map[$asset->getAssetName()] = $asset->toArray();
        $this->isDirty = true;
    }

    /**
     * Gets the asset url from map.
     *
     * @return CacheEntry | false
     */
    public function getAsset(CacheEntry $asset): CacheEntry|false
    {
        if (! $this->isActive || ! ($this->map[$asset->getAssetName()] ?? false)) {
            return false;
        }

        return CacheEntry::from($this->map[$asset->getAssetName()]);
    }

    public function delete(CacheEntry $asset): void
    {
        if (! $this->isActive) {
            return;
        }

        unset($this->map[$asset->getAssetName()]);
        $this->isDirty = true;
    }

    public function getMap(): array
    {
        return $this->map;
    }
}
