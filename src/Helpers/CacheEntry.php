<?php

namespace Backpack\Basset\Helpers;

use Backpack\Basset\Support\HasPath;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\File;
use JsonSerializable;

final class CacheEntry implements Arrayable, JsonSerializable
{
    use HasPath;

    private string $assetName;

    private string $assetPath;

    private string $assetDiskPath;

    private array $attributes = [];

    private string $content_hash = '';

    public function __construct(private string $basePath)
    {
    }

    public static function from(array $asset, $basePath): self
    {
        $instance = new self($basePath);

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

        if (! isset($this->assetDiskPath)) {
            $this->assetDiskPath = $this->getPathOnDisk($assetPath);
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

    public function existsOnDisk(Filesystem $disk): bool
    {
        return isset($this->assetDiskPath) && $disk->exists($this->assetDiskPath);
    }

    public function existsOnLocalPath()
    {
        return File::exists($this->assetPath);
    }

    public function toArray(): array
    {
        return [
            'asset_name' => $this->assetName,
            'asset_path' => $this->assetPath,
            'asset_disk_path' => isset($this->assetDiskPath) ? $this->assetDiskPath : $this->getPathOnDisk($this->assetPath),
            'attributes' => $this->attributes,
            'content_hash' => $this->content_hash,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getContents(bool $generateHash = true): string
    {
        try {
            $content = File::get($this->assetPath);
        }catch (\Exception $e) {
            throw new \Exception("Could not read file: {$this->assetPath}");
        }

        if($generateHash) {
            $this->generateContentHash($content);
        }

        return $content;
    }

    public function getContentHash(): string
    {
        return $this->content_hash;
    }

    public function getPathOnDiskHashed(string $content): string
    {
        $path = $this->getPathOnDisk($this->assetPath);

        // get the hash for the content
        $hash = hash('xxh32', $content);

        $this->content_hash = $hash;

        return preg_replace('/\.(css|js)$/i', "-{$hash}.$1", $path);
    }

    public function generateContentHash(string $content = null): void
    {
        $this->content_hash = hash('xxh32', $content ?? $this->getContents(false));
    }

    private function getPathOnDisk(string $asset): string
    {
        return $this->getPath($asset);
    }
}
