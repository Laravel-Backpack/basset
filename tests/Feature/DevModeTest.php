<?php

use Backpack\Basset\Enums\StatusEnum;
use Illuminate\Support\Facades\Http;

it('ignores cdn basset on dev mode', function ($asset) {
    // set dev mode
    config(['backpack.basset.dev_mode' => true]);

    $result = basset($asset, false);
    $path = basset()->getPath($asset);

    // assert file was not saved
    disk()->assertMissing($path);

    // assert no download was tried
    Http::assertSentCount(0);

    expect($result)->toBe(StatusEnum::DISABLED);
})->with('cdn');

it('re-caches local basset on dev mode', function ($asset) {
    // set dev mode
    config(['backpack.basset.dev_mode' => true]);

    // create the stub resource in disk
    disk()->put($asset, getStub($asset));
    $path = disk()->path($asset);

    // cache the file
    $result = basset($path, false);
    $path = basset()->getPath($path);

    // assert file was not saved
    disk()->assertExists($path);

    expect($result)->toBe(StatusEnum::CACHED);
})->with('local');

it('ignores basset block on dev mode', function ($asset) {
    // set dev mode
    config(['backpack.basset.dev_mode' => true]);

    $codeBlock = getStub($asset);

    $result = basset()->bassetBlock($asset, $codeBlock, false);

    $path = basset()->getPathHashed($asset, $codeBlock);

    // expect the output string
    $this->expectOutputString($codeBlock);

    // assert file was not saved
    disk()->assertMissing($path);

    expect($result)->toBe(StatusEnum::DISABLED);
})->with('codeBlock');
