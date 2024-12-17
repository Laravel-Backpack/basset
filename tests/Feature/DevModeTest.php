<?php

use Backpack\Basset\Enums\StatusEnum;
use Illuminate\Support\Facades\Http;

it('ignores cdn basset on dev mode', function ($asset) {
    // set dev mode
    config(['backpack.basset.dev_mode' => true]);
    config(['backpack.basset.always_cache_external_urls' => false]);

    $result = bassetInstance($asset, false);
    $path = bassetInstance()->assetPathsManager->getPathOnDisk($asset);

    // assert file was not saved
    disk()->assertMissing($path);

    // assert no download was tried
    Http::assertSentCount(0);

    expect($result)->toBe(StatusEnum::DISABLED);
})->with('cdn');

it('re-internalizes local basset on dev mode', function ($asset) {
    // set dev mode
    config(['backpack.basset.dev_mode' => true]);
    config(['backpack.basset.always_cache_external_urls' => false]);

    disk()->deleteDirectory('basset');

    // create the stub resource in disk
    disk()->put($asset, getStub($asset));
    $path = disk()->path($asset);

    // internalize the file
    $result = bassetInstance($path, false);
    $path = bassetInstance()->assetPathsManager->getPathOnDisk($path);

    // assert file was not saved
    disk()->assertExists($path);

    expect($result)->toBe(StatusEnum::INTERNALIZED);
})->with('local');

it('ignores basset block on dev mode', function ($asset) {
    // set dev mode
    config(['backpack.basset.dev_mode' => true]);

    $codeBlock = getStub($asset);

    $result = bassetInstance()->bassetBlock($asset, $codeBlock, false);

    $path = bassetInstance()->buildCacheEntry($asset)->getPathOnDiskHashed($codeBlock);

    // expect the output string
    $this->expectOutputString($codeBlock);

    // assert file was not saved
    disk()->assertMissing($path);

    expect($result)->toBe(StatusEnum::DISABLED);
})->with('codeBlock');

it('internalizes basset urls if force url cache is set and devmode is on', function ($asset) {
    // set dev mode
    config(['backpack.basset.dev_mode' => true]);
    config(['backpack.basset.always_cache_external_urls' => true]);

    $result = bassetInstance($asset, false);
    $path = bassetInstance()->assetPathsManager->getPathOnDisk($asset);

    // assert file was not saved
    expect(disk()->get($path))->toBe(getStub("$asset.output"));

    // assert no download was tried
    Http::assertSentCount(1);

    expect($result)->toBe(StatusEnum::INTERNALIZED);
})->with('cdn');
