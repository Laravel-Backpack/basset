<?php

namespace Backpack\Basset\Helpers;

interface CacheBusterInterface
{
    /**
     * Get the cache buster string for basset files.
     */
    public static function getCacheBusterString(): string;
}
