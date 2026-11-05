<?php

use Backpack\Basset\Enums\StatusEnum;
use Illuminate\Support\Facades\Http;

test('confirm environment is set to testing', function () {
    expect(config('app.env'))->toBe('workbench');
});

it('fails on invalid path', function () {
    $result = bassetInstance('invalid path', false);

    expect($result)->toBe(StatusEnum::INVALID);
});

it('cleans the pathname of an asset', function ($asset, $path) {
    $generatedPath = bassetInstance()->getPath($asset);

    expect((string) $generatedPath)->toBe("basset/$path");
})->with('paths');

it('downloads a cdn basset', function ($asset) {
    $result = bassetInstance($asset, false);

    Http::assertSentCount(1);

    expect($result)->toBe(StatusEnum::INTERNALIZED);
})->with('cdn');

it('stores a downloaded basset', function ($asset) {
    $result = bassetInstance($asset, false);
    $path = bassetInstance()->getPath($asset);

    disk()->assertExists($path);

    expect($result)->toBe(StatusEnum::INTERNALIZED);
})->with('cdn');

it('cleans the content of a downloaded basset', function ($asset) {
    bassetInstance($asset, false);
    $path = bassetInstance()->getPath($asset);

    expect(disk()->get($path))->toBe(getStub("$asset.output"));
})->with('cdn');

it('does not change the content of a downloaded basset that has an integrity hash', function ($asset) {
    $integrity = 'sha384-'.base64_encode(hash('sha384', getStub($asset), true));

    bassetInstance($asset, false, ['integrity' => $integrity, 'crossorigin' => 'anonymous']);
    $path = bassetInstance()->getPath($asset);

    expect(disk()->get($path))->toBe(getStub($asset));
})->with('cdn');

it('does not change the content of a local basset that has an integrity hash', function ($asset) {
    // create the stub resource in disk
    disk()->put($asset, getStub($asset));
    $path = disk()->path($asset);
    $integrity = 'sha384-'.base64_encode(hash('sha384', getStub($asset), true));

    // internalize the file
    $result = bassetInstance($path, false, ['integrity' => $integrity]);
    $path = bassetInstance()->getPath($path);

    expect(disk()->get($path))->toBe(getStub($asset));

    expect($result)->toBe(StatusEnum::INTERNALIZED);
})->with('local');

it('copies a local basset', function ($asset) {
    // prepare test
    disk()->put($asset, getStub($asset));
    $path = disk()->path($asset);

    $result = bassetInstance($path, false);

    Http::assertSentCount(0);

    expect($result)->toBe(StatusEnum::INTERNALIZED);
})->with('local');

it('stores a local basset', function ($asset) {
    // create the stub resource in disk
    disk()->put($asset, getStub($asset));
    $path = disk()->path($asset);

    // internalize the file
    $result = bassetInstance($path, false);
    $path = bassetInstance()->getPath($path);

    disk()->assertExists($path);

    expect($result)->toBe(StatusEnum::INTERNALIZED);
})->with('local');

it('cleans the content of a local basset', function ($asset) {
    // create the stub resource in disk
    disk()->put($asset, getStub($asset));
    $path = disk()->path($asset);

    // internalize the file
    $result = bassetInstance($path, false);
    $path = bassetInstance()->getPath($path);

    expect(disk()->get($path))->toBe(getStub("$asset.output"));

    expect($result)->toBe(StatusEnum::INTERNALIZED);
})->with('local');

it('does not download twice', function ($asset) {
    // first call should download
    $result = bassetInstance($asset, false);

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    // second call asset should be already loaded
    $result = bassetInstance($asset);

    expect($result)->toBe(StatusEnum::LOADED);

    // only 1 call could have been made to http
    Http::assertSentCount(1);
})->with('cdn');

it('does not output when not required', function ($asset) {
    bassetInstance($asset, false);

    $this->expectOutputString('');
})->with('cdn');

it('retrieves from cache when available', function ($asset) {
    // store the stub in disk
    $generatedPath = bassetInstance()->getPath($asset);
    disk()->put($generatedPath, getStub($asset));

    // should not download
    $result = bassetInstance($asset, false);

    expect($result)->toBe(StatusEnum::IN_CACHE);

    // no call could have been made to http
    Http::assertSentCount(0);
})->with('cdn');

it('works with the basset helper method', function ($asset) {
    $result = basset($asset);
    $path = bassetInstance()->getPath($asset);

    expect($result)->toBe(disk()->url($path));
})->with('cdn');
