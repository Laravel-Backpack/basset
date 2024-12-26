<?php

namespace Backpack\Basset\Helpers;

use Backpack\Basset\Contracts\AssetHashManager;
use Backpack\Basset\Contracts\AssetPathManager;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonSerializable;

final class CacheEntry implements Arrayable, JsonSerializable
{
    private string $assetName;

    private string $assetPath;

    private string $assetDiskPath;

    private array $attributes = [];

    private string $content_hash = '';

    private AssetPathManager $assetPathsManager;

    private AssetHashManager $assetHashManager;

    public function __construct()
    {
        $this->assetPathsManager = app(AssetPathManager::class);
        $this->assetHashManager = app(AssetHashManager::class);
    }

    public static function from(array $asset): self
    {
        $instance = new self();

        return $instance->assetName($asset['asset_name'])->assetPath($asset['asset_path'])->assetDiskPath($asset['asset_disk_path'])->attributes($asset['attributes']);
    }

    public function assetName(string $assetName): self
    {
        $this->assetName = $assetName;

        return $this;
    }

    public function assetPath(string $assetPath): self
    {
        $this->assetPath = $assetPath;

        if (! str_starts_with(base_path(), $assetPath) && ! Str::isUrl($assetPath)) {
            // if asset path is not a full path, we assume it's a relative path to the public folder
            $this->assetPath = public_path($assetPath);
            $this->assetDiskPath = $this->assetPathsManager->getCleanPath($this->assetPath);
        }

        if (! isset($this->assetDiskPath)) {
            $this->assetDiskPath = $this->getPathOnDisk($this->assetPath);
        }

        return $this;
    }

    public function assetDiskPath(string $assetDiskPath): self
    {
        $this->assetDiskPath = $assetDiskPath;

        return $this;
    }

    public function attributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getAssetPath(): string
    {
        return $this->assetPath;
    }

    public function getAssetDiskPath(): string
    {
        return $this->assetDiskPath;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAssetName(): string
    {
        return $this->assetName;
    }

    /**
     * Check if the asset exists in a given disk.
     *
     * @param  Filesystem  $disk
     * @return bool
     */
    public function existsOnDisk(Filesystem $disk): bool
    {
        return isset($this->assetDiskPath) && $disk->exists($this->assetDiskPath);
    }

    /**
     * Check if the asset is a local file.
     *
     * @return bool
     */
    public function isLocalAsset()
    {
        return $this->assetPathsManager->isLocal($this->assetPath);
    }

    public function toArray(): array
    {
        return [
            'asset_name'      => $this->assetName,
            'asset_path'      => $this->assetPath,
            'asset_disk_path' => isset($this->assetDiskPath) ? $this->assetDiskPath : $this->getPathOnDisk($this->assetPath),
            'attributes'      => $this->attributes,
            'content_hash'    => $this->content_hash,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getContent(): string
    {
        try {
            $content = File::get($this->assetPath);
        } catch (\Exception $e) {
            throw new \Exception("Could not read file: {$this->assetPath}");
        }

        return $content;
    }

    public function getContentAndGenerateHash(): string
    {
        $content = $this->getContent();
        $this->assetHashManager->generateHash($content);

        return $content;
    }

    public function getContentHash(): string
    {
        return $this->content_hash;
    }

    public function getPathOnDiskHashed(string $content): string
    {
        $path = $this->assetPathsManager->getPathOnDisk($this->assetPath);

        // get the hash for the content
        $hash = $this->assetHashManager->generateHash($content);

        $this->content_hash = $this->assetHashManager->appendHashToPath($content, $hash);

        return $this->assetHashManager->appendHashToPath($path, $hash);
    }

    public function generateContentHash(string $content = null): string
    {
        $content = $content ?? $this->getContent();

        return $this->content_hash = $this->assetHashManager->generateHash($content);
    }

    private function getPathOnDisk(string $asset): string
    {
        return $this->assetPathsManager->getPathOnDisk($asset);
    }
}
