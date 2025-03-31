<?php

use Backpack\Basset\BassetManager;
use Backpack\Basset\Enums\StatusEnum;
use Backpack\Basset\Tests\BaseTest;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        // setup fake links
        Http::fake([
            'https://unpkg.com/vue@3/dist/vue.global.prod.js' => Http::response(getStub('vue.global.prod.js')),
            'https://unpkg.com/react@18/umd/react.production.min.js' => Http::response(getStub('react.production.min.js')),
        ]);

        // setup config
        config([
            'backpack.basset.disk' => 'basset',
            'backpack.basset.path', 'basset',
        ]);
    })
    ->in(__DIR__);

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

function bassetInstance(?string $asset = null, bool $output = true, array $attributes = []): StatusEnum|BassetManager
{
    return $asset ? app('basset')->basset(...func_get_args()) : app('basset');
}

function getStub(string $asset): string
{
    $name = Str::of($asset)->afterLast('/');

    return File::get("tests/Helpers/$name.stub");
}

function disk(): FilesystemAdapter
{
    /** @var FilesystemAdapter */
    $disk = Storage::disk('basset');

    return $disk;
}
