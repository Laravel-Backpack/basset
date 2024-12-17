<?php

namespace Backpack\Basset\Contracts;

interface AssetPathManager
{
    public function getBasePath(): string;
    public function isLocal(string $path): bool;
    public function getPathOnDisk(string $path): string;
}