<?php

namespace  Backpack\Basset;

use Backpack\Basset\Contracts\AssetPathManagerInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class AssetPathManager implements AssetPathManagerInterface
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = (string) Str::of(config('backpack.basset.path'))->finish('/');
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getPathOnDisk(string $asset): string
    {
        return Str::of($this->basePath)
            ->append($this->getCleanPath($asset))
            ->replace(['//'], '/');
    }

    public function getCleanPath(string $asset): string
    {
        return Str::of($asset)
            ->replace([base_path().'/', base_path(), base_path().'\\', 'http://', 'https://', '://', '<', '>', ':', '"', '|', "\0", '*', '`', ';', "'", '+'], '')
            ->before('?')
            ->replace(['/\\', '\\'], '/');
    }

    public function isLocal(string $path): bool
    {
        return File::exists($path);
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }
}
