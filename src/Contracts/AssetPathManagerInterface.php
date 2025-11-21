<?php

namespace Backpack\Basset\Contracts;

interface AssetPathManagerInterface
{
    public function getBasePath(): string;

    public function isLocal(string $path): bool;

    public function getPathOnDisk(string $path): string;

    public function getCleanPath(string $path): string;
}
