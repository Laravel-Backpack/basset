<?php

use DigitallyHappy\Assets\Tests\BaseTest;
use Illuminate\Contracts\Filesystem\Filesystem;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(BaseTest::class)
    ->beforeEach(function () {
        // clear Storage
        Storage::fake('basset');

        Http::fake([
            'https://unpkg.com/vue@3/dist/vue.global.prod.js' => Http::response(getStub('vue.global.prod.js')),
            'https://unpkg.com/react@18/umd/react.production.min.js' => Http::response(getStub('react.production.min.js')),
            'https://unpkg.com/angular@1.8/angular.min.js' => Http::response(getStub('angular.min.js')),
        ]);

        // setup config
        config([
            'digitallyhappy.assets.disk' => 'basset',
            'digitallyhappy.assets.path', 'bassets',
        ]);
    })
    ->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function basset(string $asset = null, bool $output = true, array $attributes = [])
{
    return $asset ? app('assets')->basset(...func_get_args()) : app('assets');
}

function getStubName(string $asset): string
{
    return (string) Str::of($asset)->afterLast('/');
}

function getStub(string $asset): string
{
    $name = getStubName($asset);

    return File::get("tests/Helpers/$name.stub");
}

function disk(): Filesystem
{
    return Storage::disk('basset');
}
