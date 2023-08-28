<?php

it('is not loaded', function ($asset) {
    expect(bassetInstance()->isLoaded($asset))->toBe(false);
})->with('cdn');

it('gets loaded', function ($asset) {
    bassetInstance()->markAsLoaded($asset);

    expect(bassetInstance()->isLoaded($asset))->toBe(true);

    expect(bassetInstance()->loaded())->toBeArray()->toHaveCount(1);
})->with('cdn');

it('does not get loaded twice', function ($asset) {
    bassetInstance()->markAsLoaded($asset);

    // try to load it twice
    bassetInstance()->markAsLoaded($asset);

    expect(bassetInstance()->loaded())->toBeArray()->toHaveCount(1);
})->with('cdn');
