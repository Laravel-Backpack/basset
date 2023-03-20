<?php

namespace Backpack\Basset\Facades;

use Illuminate\Support\Facades\Facade;

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
