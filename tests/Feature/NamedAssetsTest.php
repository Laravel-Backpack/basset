<?php

use Backpack\Basset\Enums\StatusEnum;

it('internalizes named assets', function ($name, $url, $newVersion) {
    bassetInstance()->map($name, $url);

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    $path = bassetInstance()->getPath($url);

    disk()->assertExists($path);

    expect(disk()->get($path))->toBe(getStub($url.'.output'));
})->with('namedAssets');

it('internalizes named assets urls with dev mode true and force url cache', function ($name, $url, $newVersion) {
    bassetInstance()->map($name, $url);
    // set dev mode
    config(['backpack.basset.dev_mode' => true]);
    config(['backpack.basset.force_url_cache' => true]);

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    $path = bassetInstance()->getPath($url);

    disk()->assertExists($path);

    expect(disk()->get($path))->toBe(getStub($url.'.output'));
})->with('namedAssets');

it('replaces named assets if version changed and its already cached', function ($name, $url, $newVersion) {
    bassetInstance()->map($name, $url);
    // set dev mode
    config(['backpack.basset.dev_mode' => true]);

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    $oldPath = bassetInstance()->getPath($url);

    disk()->assertExists($oldPath);

    expect(disk()->get($oldPath))->toBe(getStub($oldPath.'.output'));

    // change the version
    bassetInstance()->map($name, $newVersion);

    bassetInstance()->clearLoadedAssets();

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    $path = bassetInstance()->getPath($newVersion);

    disk()->assertExists($path);

    expect(disk()->get($path))->toBe(getStub($newVersion.'.output'));

    // assert old version was deleted
    disk()->assertMissing($oldPath);
})->with('namedAssets');

it('does not replace named assets if version did not change and its already cached', function ($name, $url, $newVersion) {
    bassetInstance()->map($name, $url);
    // set dev mode
    config(['backpack.basset.dev_mode' => true]);

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    $oldPath = bassetInstance()->getPath($url);

    disk()->assertExists($oldPath);

    expect(disk()->get($oldPath))->toBe(getStub($oldPath.'.output'));

    bassetInstance()->clearLoadedAssets();

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::IN_CACHE);

    disk()->assertExists($oldPath);
})->with('namedAssets');

it('uses named assets attributes', function ($name, $url, $newVersion) {
    bassetInstance()->map($name, $url, ['integrity' => 'something']);

    ob_start();
    $result = bassetInstance($name, true);
    $echo = ob_get_clean();

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    expect($echo)->toContain('integrity="something"');
})->with('namedAssetsOutput');

it('uses named assets attributes and allow overwrite', function ($name, $url, $newVersion) {
    bassetInstance()->map($name, $url, ['integrity' => 'something', 'test' => 'something']);

    ob_start();
    $result = bassetInstance($name, true, ['integrity' => 'something-else']);
    $echo = ob_get_clean();

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    expect($echo)->toContain('integrity="something-else"');
})->with('namedAssetsOutput');
