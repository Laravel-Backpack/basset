<?php

namespace Backpack\Basset\Helpers;

class CacheBuster implements CacheBusterInterface
{
    /**
     * Get the cache buster string.
     */
    public static function getCacheBusterString(): string
    {
        return substr(md5(base_path('composer.lock')), 0, 12);
    }
}
