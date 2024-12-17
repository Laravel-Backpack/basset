<?php

use Backpack\Basset\Enums\StatusEnum;
use Illuminate\Support\Facades\Http;

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
