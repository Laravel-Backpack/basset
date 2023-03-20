<?php

namespace Backpack\Basset\Tests;

use Backpack\Basset\BassetServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class BaseTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            BassetServiceProvider::class,
        ];
    }
}
