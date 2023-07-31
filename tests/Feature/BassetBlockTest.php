<?php

use Backpack\Basset\Enums\StatusEnum;

it('stores basset block', function ($asset) {
    $codeBlock = getStub($asset);

    $result = basset()->bassetBlock($asset, $codeBlock, false);

    $path = basset()->getPathHashed($asset, $codeBlock);

    disk()->assertExists($path);

    expect($result)->toBe(StatusEnum::CACHED);
})->with('codeBlock');

it('cleans basset block', function ($asset) {
    $codeBlock = getStub($asset);

    basset()->bassetBlock($asset, $codeBlock, false);

    $path = basset()->getPathHashed($asset, $codeBlock);

    // validate the ouput content
    expect(getStub("$asset.output"))->toBe(disk()->get($path));
})->with('codeBlock');
