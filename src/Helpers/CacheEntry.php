<?php

namespace Backpack\Basset\Helpers;

use Backpack\Basset\AssetHashManager;
use Backpack\Basset\AssetPathManager;
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

    private array $assetAttributes = [];

    private string $assetContentHash = '';

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

        return $instance->assetName($asset['asset_name'])->assetPath($asset['asset_path'])->assetDiskPath($asset['asset_disk_path'])->assetAttributes($asset['asset_attributes']);
    }

    public function assetName(string $assetName): self
    {
        $this->assetName = $assetName;

        return $this;
    }

    public function assetPath(string $assetPath): self
    {
        $this->assetPath = $assetPath;

        if (! str_starts_with($assetPath, base_path()) && ! Str::isUrl($assetPath)) {
            if (File::exists(public_path($assetPath))) {
                $this->assetPath = public_path($assetPath);
                $this->assetDiskPath = $this->assetPathsManager->getCleanPath($assetPath);
            } else {
                $this->assetPath = base_path($assetPath);
            }
        }

        if (! isset($this->assetDiskPath)) {
            $this->assetDiskPath = $this->getPathOnDisk($this->assetPathsManager->getCleanPath($assetPath));
        }

        if ($this->isLocalAsset()) {
            $this->generateContentHash();
        }

        return $this;
    }

    public function assetDiskPath(string $assetDiskPath): self
    {
        $this->assetDiskPath = $assetDiskPath;

        return $this;
    }

    public function assetAttributes(array $attributes): self
    {
        $this->assetAttributes = $attributes;

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
        return $this->assetAttributes;
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
            'asset_name' => $this->assetName,
            'asset_path' => $this->assetPath,
            'asset_disk_path' => isset($this->assetDiskPath) ? $this->assetDiskPath : $this->getPathOnDisk($this->assetPath),
            'asset_attributes' => $this->assetAttributes,
            'asset_content_hash' => $this->assetContentHash,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getContent(): string
    {
        try {
            if (! File::isFile($this->assetPath) && ! Str::isUrl($this->assetPath)) {
                return $this->assetPath;
            }
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
        return $this->assetContentHash;
    }

    public function getPathOnDiskHashed(string $content): string
    {
        $path = $this->assetPathsManager->getPathOnDisk($this->assetPath);

        // get the hash for the content
        $hash = $this->assetHashManager->generateHash($content);

        $this->assetContentHash = $this->assetHashManager->appendHashToPath($content, $hash);

        return $this->assetHashManager->appendHashToPath($path, $hash);
    }

    public function generateContentHash(?string $content = null): string
    {
        $content = $content ?? $this->getContent();

        return $this->assetContentHash = $this->assetHashManager->generateHash($content);
    }

    private function getPathOnDisk(string $asset): string
    {
        return $this->assetPathsManager->getPathOnDisk($asset);
    }

    public function getOutputDiskPath(): string
    {
        $diskPath = Str::of(config('filesystems.disks.'.config('backpack.basset.disk'))['url'])->finish('/');

        return (string) Str::of($diskPath.$this->assetDiskPath);
    }
}
