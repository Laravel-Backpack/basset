<?php

use Backpack\Basset\Enums\StatusEnum;

it('stores basset block', function ($asset) {
    $codeBlock = getStub($asset);

    $result = bassetInstance()->bassetBlock($asset, $codeBlock, false);

    $path = bassetInstance()->getPathHashed($asset, $codeBlock);

    disk()->assertExists($path);

    expect($result)->toBe(StatusEnum::INTERNALIZED);
})->with('codeBlock');

it('cleans basset block', function ($asset) {
    $codeBlock = getStub($asset);

    bassetInstance()->bassetBlock($asset, $codeBlock, false);

    $path = bassetInstance()->getPathHashed($asset, $codeBlock);

    // validate the output content
    expect(getStub("$asset.output"))->toBe(disk()->get($path));
})->with('codeBlock');
