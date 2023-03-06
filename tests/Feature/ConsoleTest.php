<?php

it('internalizes assets via console command', function () {
    // setup a sample folder
    config(['digitallyhappy.assets.view_paths' => [disk()->path('')]]);

    // create a sample resource
    disk()->put('sample.blade.php', getStub('internalize.blade.php'));
    disk()->put('sample.js', getStub('vue.global.prod.js'));

    $this->artisan('basset:internalize')
        ->expectsOutputToContain('Looking for assets under the following directories:')
        ->expectsOutputToContain('(1 blade files)')
        ->expectsOutputToContain('Found 1 assets in 1 blade files.')
        ->assertExitCode(0);
});

it('clears basset folder via console command', function () {
    $path = config('digitallyhappy.assets.path');

    // polute with a sample file
    disk()->put("$path/sample.js", 'sample');

    $this->artisan('basset:clear')->assertExitCode(0);

    disk()->assertExists($path)->assertDirectoryEmpty($path);
});
