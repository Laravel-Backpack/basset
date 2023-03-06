<?php

use DigitallyHappy\Assets\AssetManager;

test('confirm environment is set to testing', function () {
    expect(config('app.env'))->toBe('testing');
});

it('fails on invalid path', function () {
    $result = basset('invalid path', false);

    expect($result)->toBe(AssetManager::STATUS_INVALID);
});

it('cleans the pathname of an asset', function ($cdn, $path) {
    config(['digitallyhappy.assets.path', 'bassets']);

    $generatedPath = basset()->getAssetPath($cdn);

    expect((string) $generatedPath)->toBe("bassets/$path");

})->with('paths');

it('downloads a cdn basset', function ($cdn) {
    $result = basset($cdn, false);

    Http::assertSentCount(1);

    expect($result)->toBe(AssetManager::STATUS_DOWNLOADED);

})->with('cdn');

it('stores a downloaded basset', function ($cdn) {
    $result = basset($cdn, false);
    $path = basset()->getAssetPath($cdn);

    disk()->assertExists($path);

    expect($result)->toBe(AssetManager::STATUS_DOWNLOADED);

})->with('cdn');

it('cleans the content of a downloaded basset', function ($cdn) {
    basset($cdn, false);
    $path = basset()->getAssetPath($cdn);

    expect(disk()->get($path))->toBe(getStub("$cdn.output"));

})->with('cdn');

it('copies a local basset', function ($asset) {
    // prepare test
    disk()->put($asset, getStub($asset));
    $path = disk()->path($asset);

    $result = basset($path, false);

    Http::assertSentCount(0);

    expect($result)->toBe(AssetManager::STATUS_DOWNLOADED);

})->with('local');

it('stores a local basset', function ($asset) {
    // create the stub resource in disk
    disk()->put($asset, getStub($asset));
    $path = disk()->path($asset);

    // internalize the file
    $result = basset($path, false);
    $path = basset()->getAssetPath($path);

    disk()->assertExists($path);

    expect($result)->toBe(AssetManager::STATUS_DOWNLOADED);

})->with('local');

it('cleans the content of a local basset', function ($asset) {
    // create the stub resource in disk
    disk()->put($asset, getStub($asset));
    $path = disk()->path($asset);

    // internalize the file
    $result = basset($path, false);
    $path = basset()->getAssetPath($path);

    expect(disk()->get($path))->toBe(getStub("$asset.output"));

    expect($result)->toBe(AssetManager::STATUS_DOWNLOADED);

})->with('local');

it('does not download twice', function ($cdn) {
    // first call should download
    $result = basset($cdn, false);

    expect($result)->toBe(AssetManager::STATUS_DOWNLOADED);

    // second call asset should be already loaded
    $result = basset($cdn);

    expect($result)->toBe(AssetManager::STATUS_LOADED);

    // only 1 call could have been made to http
    Http::assertSentCount(1);

})->with('cdn');

it('does not output when not required', function ($cdn) {
    basset($cdn, false);

    expect($this->getActualOutput())->toBeEmpty();
})->with('cdn');

it('retreives from cache when available', function ($cdn) {
    // store the stub in disk
    $generatedPath = basset()->getAssetPath($cdn);
    disk()->put($generatedPath, getStub($cdn));

    // should not download
    $result = basset($cdn, false);

    expect($result)->toBe(AssetManager::STATUS_IN_CACHE);

    // no call could have been made to http
    Http::assertSentCount(0);

})->with('cdn');
