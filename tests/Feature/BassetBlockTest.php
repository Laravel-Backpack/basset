<?php

it('stores basset block', function ($asset) {
    $codeBlock = getStub($asset);

    $result = basset()->bassetBlock($asset, $codeBlock, false);

    disk()->assertExists(basset()->getAssetPath($asset));

    expect($result)->toBeNull();
})->with('codeBlock');

it('cleans basset block', function ($asset) {
    $codeBlock = getStub($asset);

    basset()->bassetBlock($asset, $codeBlock, false);
    $path = basset()->getAssetPath($asset);

    // validate the ouput content
    expect(getStub("$asset.output"))->toBe(disk()->get($path));
})->with('codeBlock');
