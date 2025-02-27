<?php

namespace Backpack\Basset;

use Backpack\Basset\Contracts\AssetHashManagerInterface;

final class AssetHashManager implements AssetHashManagerInterface
{
    public function generateHash(string $content): string
    {
        return hash('xxh32', $content);
    }

    public function appendHashToPath(string $path, string $hash): string
    {
        return preg_replace('/\.(css|js)$/i', "-{$hash}.$1", $path);
    }

    public function validateHash(string $content, string $hash): bool
    {
        return $this->generateHash($content) === $hash;
    }
}
