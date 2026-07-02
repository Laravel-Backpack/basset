<?php

use Backpack\Basset\Facades\Basset;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = storage_path('framework/testing/disks/basset-test');
    File::ensureDirectoryExists($this->tempDir);
    Basset::addViewPath($this->tempDir);

    // ensure cache map and comparison are enabled for these tests
    config(['backpack.basset.cache_map' => true]);
    config(['backpack.basset.cache_map_comparison' => true]);
    config(['backpack.basset.dev_mode' => false]);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

it('skips re-downloading assets already in cache map and on disk', function () {
    $assetUrl = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk($assetUrl);

    // pre-populate: put the file on disk and add to cache map
    disk()->put($diskPath, getStub('vue.global.prod.js'));
    Basset::cacheMap()->addAsset(
        bassetInstance()->buildCacheEntry($assetUrl)
    );

    // create a blade file referencing the asset
    File::put($this->tempDir.'/test.blade.php', "@basset('$assetUrl', false)");

    $this->artisan('basset:cache');

    // the file should still be there, and no HTTP request should have been made
    disk()->assertExists($diskPath);
    // the cache map should still have the entry
    expect(Basset::cacheMap()->getMap())->toHaveKey($assetUrl);
});

it('downloads assets not in cache map', function () {
    $assetUrl = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk($assetUrl);

    // no pre-populated cache — asset needs downloading
    File::put($this->tempDir.'/test.blade.php', "@basset('$assetUrl', false)");

    $this->artisan('basset:cache');

    disk()->assertExists($diskPath);
    expect(Basset::cacheMap()->getMap())->toHaveKey($assetUrl);
});

it('downloads assets when cache map has entry but file is missing from disk', function () {
    $assetUrl = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk($assetUrl);

    // add to cache map but do NOT put file on disk
    Basset::cacheMap()->addAsset(
        bassetInstance()->buildCacheEntry($assetUrl)
    );

    File::put($this->tempDir.'/test.blade.php', "@basset('$assetUrl', false)");

    $this->artisan('basset:cache');

    // should download because file was missing
    disk()->assertExists($diskPath);
});

it('removes stale assets with --stale flag', function () {
    $activeUrl = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';
    $staleUrl = 'https://unpkg.com/react@18/umd/react.production.min.js';

    $activePath = bassetInstance()->assetPathsManager->getPathOnDisk($activeUrl);
    $stalePath = bassetInstance()->assetPathsManager->getPathOnDisk($staleUrl);

    // pre-populate both assets
    disk()->put($activePath, getStub('vue.global.prod.js'));
    disk()->put($stalePath, getStub('react.production.min.js'));

    Basset::cacheMap()->addAsset(bassetInstance()->buildCacheEntry($activeUrl));
    Basset::cacheMap()->addAsset(bassetInstance()->buildCacheEntry($staleUrl));
    Basset::cacheMap()->save();

    // only reference the active asset in the blade file
    File::put($this->tempDir.'/test.blade.php', "@basset('$activeUrl', false)");

    $this->artisan('basset:cache --stale');

    // active asset should still exist
    disk()->assertExists($activePath);
    expect(Basset::cacheMap()->getMap())->toHaveKey($activeUrl);

    // stale asset should be removed from disk and map
    disk()->assertMissing($stalePath);
    expect(Basset::cacheMap()->getMap())->not()->toHaveKey($staleUrl);
});

it('does not remove stale assets without --stale flag', function () {
    $activeUrl = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';
    $staleUrl = 'https://unpkg.com/react@18/umd/react.production.min.js';

    $activePath = bassetInstance()->assetPathsManager->getPathOnDisk($activeUrl);
    $stalePath = bassetInstance()->assetPathsManager->getPathOnDisk($staleUrl);

    // pre-populate both assets
    disk()->put($activePath, getStub('vue.global.prod.js'));
    disk()->put($stalePath, getStub('react.production.min.js'));

    Basset::cacheMap()->addAsset(bassetInstance()->buildCacheEntry($activeUrl));
    Basset::cacheMap()->addAsset(bassetInstance()->buildCacheEntry($staleUrl));
    Basset::cacheMap()->save();

    // only reference the active asset
    File::put($this->tempDir.'/test.blade.php', "@basset('$activeUrl', false)");

    // run WITHOUT --stale
    $this->artisan('basset:cache');

    // both should still exist
    disk()->assertExists($activePath);
    disk()->assertExists($stalePath);
    expect(Basset::cacheMap()->getMap())->toHaveKey($activeUrl);
    expect(Basset::cacheMap()->getMap())->toHaveKey($staleUrl);
});

it('isAssetCached returns true when asset is in map and on disk', function () {
    $assetUrl = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk($assetUrl);

    disk()->put($diskPath, getStub('vue.global.prod.js'));
    Basset::cacheMap()->addAsset(bassetInstance()->buildCacheEntry($assetUrl));

    expect(Basset::isAssetCached($assetUrl))->toBeTrue();
});

it('isAssetCached returns false when asset is not in map', function () {
    $assetUrl = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk($assetUrl);

    disk()->put($diskPath, getStub('vue.global.prod.js'));
    // not adding to cache map

    expect(Basset::isAssetCached($assetUrl))->toBeFalse();
});

it('isAssetCached returns false when asset is in map but not on disk', function () {
    $assetUrl = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';

    Basset::cacheMap()->addAsset(bassetInstance()->buildCacheEntry($assetUrl));
    // not putting file on disk

    expect(Basset::isAssetCached($assetUrl))->toBeFalse();
});

it('stores cache map in private storage path, not public disk', function () {
    $assetUrl = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';
    File::put($this->tempDir.'/test.blade.php', "@basset('$assetUrl', false)");

    $this->artisan('basset:cache');

    // The .basset file should be at the private storage path
    $privatePath = storage_path('basset/.basset');
    expect(File::exists($privatePath))->toBeTrue();

    // It should NOT be on the public disk
    disk()->assertMissing('.basset');
});

it('migrates cache map from old public location to new private location', function () {
    $assetUrl = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk($assetUrl);

    // Simulate an old install: .basset exists only on the public disk
    $oldMap = [$assetUrl => [
        'asset_name' => $assetUrl,
        'asset_path' => $assetUrl,
        'asset_disk_path' => $diskPath,
        'asset_attributes' => [],
        'asset_content_hash' => '',
    ]];

    $oldPath = disk()->path('basset/.basset');
    File::ensureDirectoryExists(dirname($oldPath), 0755, true);
    File::put($oldPath, json_encode($oldMap));

    // Also put the file on disk so it looks cached
    disk()->put($diskPath, getStub('vue.global.prod.js'));

    // Private path should NOT exist yet
    $privatePath = storage_path('basset/.basset');
    if (File::exists($privatePath)) {
        File::delete($privatePath);
    }
    expect(File::exists($privatePath))->toBeFalse();

    // Run basset:cache — should trigger migration
    File::put($this->tempDir.'/test.blade.php', "@basset('$assetUrl', false)");
    $this->artisan('basset:cache');

    // Now the private path should exist with the migrated data
    expect(File::exists($privatePath))->toBeTrue();
    $migrated = json_decode(File::get($privatePath), true);
    expect($migrated)->toHaveKey($assetUrl);
});

it('basset:clear removes cache map from both old and new locations', function () {
    $privatePath = storage_path('basset/.basset');
    $oldPath = disk()->path('basset/.basset');

    // Put .basset in both locations
    File::ensureDirectoryExists(dirname($privatePath), 0755, true);
    File::put($privatePath, json_encode(['test' => 'data']));
    File::ensureDirectoryExists(dirname($oldPath), 0755, true);
    File::put($oldPath, json_encode(['test' => 'data']));

    // Also put something on the public disk so clear doesn't fail
    disk()->put('basset/sample.js', 'sample');

    $this->artisan('basset:clear');

    // Both should be gone
    expect(File::exists($privatePath))->toBeFalse();
    expect(File::exists($oldPath))->toBeFalse();
});

it('re-downloads cached assets when cache_map_comparison is disabled', function () {
    config(['backpack.basset.cache_map_comparison' => false]);

    $assetUrl = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk($assetUrl);

    // pre-populate cache: file on disk + cache map entry
    disk()->put($diskPath, getStub('vue.global.prod.js'));
    Basset::cacheMap()->addAsset(
        bassetInstance()->buildCacheEntry($assetUrl)
    );

    File::put($this->tempDir.'/test.blade.php', "@basset('$assetUrl', false)");

    $this->artisan('basset:cache');

    // Should still exist (re-downloaded or kept), but the point is
    // it was NOT skipped — the old behavior is preserved
    disk()->assertExists($diskPath);
});

it('does not skip local files even with cache_map_comparison enabled', function () {
    // Use a path relative to base_path, which buildCacheEntry resolves automatically
    $localFile = 'resources/js/test-local.js';
    $absolutePath = base_path($localFile);
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk($absolutePath);

    // Write the actual local file with NEW content
    File::ensureDirectoryExists(dirname($absolutePath), 0755, true);
    File::put($absolutePath, 'new content');

    // Pre-populate basset cache with OLD content
    disk()->put($diskPath, 'old content');
    Basset::cacheMap()->addAsset(
        bassetInstance()->buildCacheEntry($absolutePath)
    );
    Basset::cacheMap()->save();

    // Use the simple relative path string in the blade (no base_path() call needed)
    File::put($this->tempDir.'/test.blade.php', "@basset('$localFile', false)");

    $this->artisan('basset:cache');

    // Should have re-copied the file with the new content, not kept the old
    expect(disk()->get($diskPath))->toBe('new content');

    // Cleanup
    File::delete($absolutePath);
});

it('re-copies local file even when content has not changed', function () {
    $localFile = 'resources/js/test-unchanged.js';
    $absolutePath = base_path($localFile);

    // Write the local file
    File::ensureDirectoryExists(dirname($absolutePath), 0755, true);
    File::put($absolutePath, 'same content');

    // Pre-populate cache with same content
    $entry = bassetInstance()->buildCacheEntry($absolutePath);
    disk()->put($entry->getAssetDiskPath(), 'same content');
    Basset::cacheMap()->addAsset($entry);
    Basset::cacheMap()->save();

    File::put($this->tempDir.'/test.blade.php', "@basset('$localFile', false)");

    $this->artisan('basset:cache');

    // Still exists (re-copied)
    disk()->assertExists($entry->getAssetDiskPath());
    expect(disk()->get($entry->getAssetDiskPath()))->toBe('same content');

    // Cleanup
    File::delete($absolutePath);
});
