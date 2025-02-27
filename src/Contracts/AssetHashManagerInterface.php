<?php

namespace Backpack\Basset\Contracts;

interface AssetHashManagerInterface
{
    public function generateHash(string $content): string;

    public function appendHashToPath(string $path, string $hash): string;

    public function validateHash(string $content, string $hash): bool;
}
