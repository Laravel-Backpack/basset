<?php

use Backpack\Basset\Enums\StatusEnum;
use Illuminate\Support\Facades\Http;

it('re-internalizes local basset on dev mode', function ($asset) {
    // set dev mode
    bassetInstance()->setDevMode(true);

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
    bassetInstance()->setDevMode(true);

    $codeBlock = getStub($asset);

    $result = bassetInstance()->bassetBlock($asset, $codeBlock, false);

    $path = bassetInstance()->buildCacheEntry($asset)->getPathOnDiskHashed($codeBlock);

    // expect the output string
    $this->expectOutputString($codeBlock);

    // assert file was not saved
    disk()->assertMissing($path);

    expect($result)->toBe(StatusEnum::DISABLED);
})->with('codeBlock');

it('internalize asset urls', function ($asset) {
    // set dev mode
    bassetInstance()->setDevMode(true);

    $result = bassetInstance($asset, false);
    $path = bassetInstance()->assetPathsManager->getPathOnDisk($asset);

    // assert file was not saved
    expect(disk()->get($path))->toBe(getStub("$asset.output"));

    // assert no download was tried
    Http::assertSentCount(1);

    expect($result)->toBe(StatusEnum::INTERNALIZED);
})->with('cdn');
