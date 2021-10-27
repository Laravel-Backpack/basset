<?php

namespace DigitallyHappy\Assets\Facades;

use Illuminate\Support\Facades\Facade;

class Assets extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'assets';
    }
}
