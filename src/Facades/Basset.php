<?php

namespace Backpack\Basset\Facades;

use Backpack\Basset\BassetManager;
use Backpack\Basset\Enums\StatusEnum;
use Illuminate\Support\Facades\Facade;

/**
 * Class Basset Facade.
 *
 * @method static void markAsLoaded(string $asset)
 * @method static bool isLoaded(string $asset)
 * @method static array loaded()
 * @method static string getPath(string $asset)
 * @method static string getPathHashed(string $asset, string $content)
 * @method static string getUrl(string $asset)
 * @method static StatusEnum basset(string $asset, bool|string $output = true, array $attributes = [])
 * @method static StatusEnum bassetBlock(string $asset, string $code, bool $output = true)
 * @method static StatusEnum bassetArchive(string $asset, string $output)
 * @method static StatusEnum bassetDirectory(string $asset, string $output)
 *
 * @mixin BassetManager
 */
class Basset extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'basset';
    }
}
