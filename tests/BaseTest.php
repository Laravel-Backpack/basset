<?php

namespace DigitallyHappy\Assets\Tests;

use DigitallyHappy\Assets\AssetsServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class BaseTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            AssetsServiceProvider::class,
        ];
    }
}
