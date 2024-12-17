<?php

namespace  Backpack\Basset;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class AssetPathManager implements \Backpack\Basset\Contracts\AssetPathManager
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
            ->append(str_replace([base_path().'/', base_path(), 'http://', 'https://', '://', '<', '>', ':', '"', '|', "\0", '*', '`', ';', "'", '+'], '', $asset))
            ->before('?')
            ->replace('/\\', '/');
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
