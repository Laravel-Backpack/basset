<?php

namespace Backpack\Basset\Facades;

use Backpack\Basset\BassetManager;
use Illuminate\Support\Facades\Facade;

/**
 * Class Basset Facade.
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
