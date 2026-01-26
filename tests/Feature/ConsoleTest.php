<?php

it('clears basset folder via console command', function () {
    $path = config('backpack.basset.path');

    // pollute with a sample file
    disk()->put("$path/sample.js", 'sample');

    $this->artisan('basset:clear')->assertExitCode(0);

    disk()->assertExists($path)->assertDirectoryEmpty($path);
});

it('clears basset folder via optimize:clear command', function () {
    $path = config('backpack.basset.path');

    // pollute with a sample file
    disk()->put("$path/sample.js", 'sample');

    $this->artisan('optimize:clear')->assertExitCode(0);

    disk()->assertExists($path)->assertDirectoryEmpty($path);
});
