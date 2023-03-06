<?php

it('is not loaded', function ($asset) {
    expect(basset()->isLoaded($asset))->toBe(false);
})->with('cdn');

it('gets loaded', function ($asset) {
    basset()->markAsLoaded($asset);

    expect(basset()->isLoaded($asset))->toBe(true);

    expect(basset()->loaded())->toBeArray()->toHaveCount(1);
})->with('cdn');

it('does not get loaded twice', function ($asset) {
    basset()->markAsLoaded($asset);

    // try to load it twice
    basset()->markAsLoaded($asset);

    expect(basset()->loaded())->toBeArray()->toHaveCount(1);
})->with('cdn');
