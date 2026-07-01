<?php

use Backpack\Basset\Facades\Basset;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = storage_path('framework/testing/disks/basset-test');
    File::ensureDirectoryExists($this->tempDir);
    Basset::addViewPath($this->tempDir);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

it('clears basset folder via console command', function () {
    $path = config('backpack.basset.path');

    // pollute with a sample file
    disk()->put("$path/sample.js", 'sample');

    $this->artisan('basset:clear')->assertExitCode(0);

    disk()->assertExists($path)->assertDirectoryEmpty($path);
});

it('caches @basset with arguments spanning multiple lines', function () {
    File::put($this->tempDir.'/test.blade.php', <<<'BLADE'
@basset('https://unpkg.com/vue@3/dist/vue.global.prod.js',
    false,
    ['async' => true])
BLADE);

    $this->artisan('basset:cache');

    $assetPath = bassetInstance()->assetPathsManager->getPathOnDisk(
        'https://unpkg.com/vue@3/dist/vue.global.prod.js'
    );
    disk()->assertExists($assetPath);
});

it('caches @basset with opening paren on its own line', function () {
    File::put($this->tempDir.'/test.blade.php', <<<'BLADE'
@basset(
    'https://unpkg.com/vue@3/dist/vue.global.prod.js',
    false
)
BLADE);

    $this->artisan('basset:cache');

    $assetPath = bassetInstance()->assetPathsManager->getPathOnDisk(
        'https://unpkg.com/vue@3/dist/vue.global.prod.js'
    );
    disk()->assertExists($assetPath);
});

it('caches @basset with closing paren on its own line', function () {
    File::put($this->tempDir.'/test.blade.php', <<<'BLADE'
@basset('https://unpkg.com/vue@3/dist/vue.global.prod.js',
    false
)
BLADE);

    $this->artisan('basset:cache');

    $assetPath = bassetInstance()->assetPathsManager->getPathOnDisk(
        'https://unpkg.com/vue@3/dist/vue.global.prod.js'
    );
    disk()->assertExists($assetPath);
});

it('caches @basset with each argument and paren on separate lines', function () {
    File::put($this->tempDir.'/test.blade.php', <<<'BLADE'
@basset(
    'https://unpkg.com/vue@3/dist/vue.global.prod.js',
    false,
    ['async' => true]
)
BLADE);

    $this->artisan('basset:cache');

    $assetPath = bassetInstance()->assetPathsManager->getPathOnDisk(
        'https://unpkg.com/vue@3/dist/vue.global.prod.js'
    );
    disk()->assertExists($assetPath);
});

it('caches multiple multi-line @basset calls without cross-matching', function () {
    File::put($this->tempDir.'/test.blade.php', <<<'BLADE'
@basset(
    'https://unpkg.com/vue@3/dist/vue.global.prod.js',
    false
)

<div>Some HTML in between</div>

@basset(
    'https://unpkg.com/react@18/umd/react.production.min.js',
    false,
    ['type' => 'module']
)
BLADE);

    $this->artisan('basset:cache');

    $vuePath = bassetInstance()->assetPathsManager->getPathOnDisk(
        'https://unpkg.com/vue@3/dist/vue.global.prod.js'
    );
    $reactPath = bassetInstance()->assetPathsManager->getPathOnDisk(
        'https://unpkg.com/react@18/umd/react.production.min.js'
    );

    disk()->assertExists($vuePath);
    disk()->assertExists($reactPath);
});

it('caches multi-line @basset with Windows CRLF line endings', function () {
    File::put($this->tempDir.'/test.blade.php', str_replace("\n", "\r\n", <<<'BLADE'
@basset('https://unpkg.com/vue@3/dist/vue.global.prod.js',
    false,
    ['async' => true])
BLADE));

    $this->artisan('basset:cache');

    $assetPath = bassetInstance()->assetPathsManager->getPathOnDisk(
        'https://unpkg.com/vue@3/dist/vue.global.prod.js'
    );
    disk()->assertExists($assetPath);
});
