<?php

use Backpack\Basset\Enums\StatusEnum;

it('internalizes named assets', function ($name, $url, $newVersion) {
    bassetInstance()->map($name, $url);

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    $path = bassetInstance()->assetPathsManager->getPathOnDisk($url);

    disk()->assertExists($path);

    expect(disk()->get($path))->toBe(getStub($url.'.output'));
})->with('namedAssets');

it('internalizes named assets urls with dev mode true and force url cache', function ($name, $url, $newVersion) {
    bassetInstance()->map($name, $url);
    // set dev mode
    bassetInstance()->setDevMode(true);

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    $path = bassetInstance()->assetPathsManager->getPathOnDisk($url);

    disk()->assertExists($path);

    expect(disk()->get($path))->toBe(getStub($url.'.output'));
})->with('namedAssets');

it('replaces named assets if version changed and its already cached', function ($name, $url, $newVersion) {
    bassetInstance()->map($name, $url);
    // set dev mode
    bassetInstance()->setDevMode(true);

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    expect(bassetInstance()->cacheMap()->getMap()[$name]['asset_path'])->toContain($url);

    $oldPath = bassetInstance()->assetPathsManager->getPathOnDisk($url);

    disk()->assertExists($oldPath);

    expect(disk()->get($oldPath))->toBe(getStub($oldPath.'.output'));

    bassetInstance()->clearNamedAssets();

    bassetInstance()->clearLoadedAssets();

    bassetInstance()->map($name, $newVersion);

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    $path = bassetInstance()->assetPathsManager->getPathOnDisk($newVersion);

    disk()->assertExists($path);

    expect(bassetInstance()->cacheMap()->getMap()[$name]['asset_path'])->toContain($newVersion);

    expect(disk()->get($path))->toBe(getStub($newVersion.'.output'));

    // assert old version was deleted
    disk()->assertMissing($oldPath);
})->with('namedAssets');

it('does not replace named assets if version did not change and its already cached', function ($name, $url, $newVersion) {
    bassetInstance()->map($name, $url);
    // set dev mode
    bassetInstance()->setDevMode(true);

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    expect(bassetInstance()->cacheMap()->getMap()[$name]['asset_path'])->toContain($url);

    $oldPath = bassetInstance()->assetPathsManager->getPathOnDisk($url);

    disk()->assertExists($oldPath);

    expect(disk()->get($oldPath))->toBe(getStub($oldPath.'.output'));

    bassetInstance()->clearLoadedAssets();

    $result = bassetInstance($name, false);

    expect($result)->toBe(StatusEnum::IN_CACHE);

    disk()->assertExists($oldPath);
})->with('namedAssets');

it('uses named assets attributes', function ($name, $url) {
    bassetInstance()->map($name, $url, ['integrity' => 'something']);

    ob_start();
    $result = bassetInstance($name, true);
    $echo = ob_get_clean();

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    expect($echo)->toContain('integrity="something"');
})->with('namedAssetsOutput');

it('uses named assets attributes and allow overwrite', function ($name, $url) {
    bassetInstance()->map($name, $url, ['integrity' => 'something', 'test' => 'something']);

    ob_start();
    $result = bassetInstance($name, true, ['integrity' => 'something-else']);
    $echo = ob_get_clean();

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    expect($echo)->toContain('integrity="something-else"');
})->with('namedAssetsOutput');

it('allow named assets to be overridden by using a class', function ($name, $url) {
    config(['backpack.basset.asset_overrides' => Backpack\Basset\Tests\Helpers\OverrideAssets::class]);
    ob_start();
    $result = bassetInstance($name, true);
    $echo = ob_get_clean();

    expect($result)->toBe(StatusEnum::INTERNALIZED);

    expect($echo)->toContain('integrity="something-else"');
})->with('namedAssetsOutput');
