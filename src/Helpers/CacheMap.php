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

        if (File::exists($this->filePath)) {
            $jsonFile = json_decode(File::get($this->filePath), true);
            //dd($jsonFile);
            foreach ($jsonFile as $assetName => $asset) {
                $this->map[$assetName] = (new CacheEntry($this->disk, $this->basePath))
                    ->assetName($asset['asset_name'])
                    ->assetPath($asset['asset_path'])
                    ->assetDiskPath($asset['asset_disk_path'])
                    ->attributes($asset['attributes']);
            }
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

        // Clean both asset and path
        //$asset = $this->normalizeAsset($asset);

        $this->map[$asset->getAssetName()] = $asset;
        $this->isDirty = true;
    }

    /**
     * Gets the asset url from map.
     *
     * @return CacheEntry | false
     */
    public function getAsset(CacheEntry $asset): CacheEntry|false
    {
        // Clean asset path
        //$asset = $this->normalizeAsset($asset);

        if (! $this->isActive || ! ($this->map[$asset->getAssetName()] ?? false)) {
            return false;
        }

        return $this->map[$asset->getAssetName()];
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
