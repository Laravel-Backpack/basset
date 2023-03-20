<?php

it('clears basset folder via console command', function () {
    $path = config('backpack.basset.path');

    // polute with a sample file
    disk()->put("$path/sample.js", 'sample');

    $this->artisan('basset:clear')->assertExitCode(0);

    disk()->assertExists($path)->assertDirectoryEmpty($path);
});
