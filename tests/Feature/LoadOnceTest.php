<?php

it('is not loaded', function ($cdn) {
    expect(basset()->isLoaded($cdn))->toBe(false);
})->with('cdn');

it('gets loaded', function ($cdn) {
    basset()->markAsLoaded($cdn);

    expect(basset()->isLoaded($cdn))->toBe(true);

    expect(basset()->loaded())->toBeArray()->toHaveCount(1);
})->with('cdn');

it('does not get loaded twice', function ($cdn) {
    basset()->markAsLoaded($cdn);

    // try to load it twice
    basset()->markAsLoaded($cdn);

    expect(basset()->loaded())->toBeArray()->toHaveCount(1);
})->with('cdn');
