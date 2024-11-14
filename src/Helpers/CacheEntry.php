<?php

namespace Backpack\Basset\Helpers;

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
            'asset_name'      => $this->assetName,
            'asset_path'      => $this->assetPath,
            'asset_disk_path' => isset($this->assetDiskPath) ? $this->assetDiskPath : $this->getPathOnDisk($this->assetPath),
            'attributes'      => $this->attributes,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function getPathOnDisk(string $asset)
    {
        return Str::of($this->basePath)
            ->append(str_replace([base_path().'/', base_path(), 'http://', 'https://', '://', '<', '>', ':', '"', '|', "\0", '*', '`', ';', "'", '+'], '', $asset))
            ->before('?')
            ->replace('/\\', '/');
    }
}
