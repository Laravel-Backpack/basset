<?php

beforeEach(function () {
    config(['backpack.basset.path' => 'basset']);
});

it('getPathOnDiskHashed stores actual hash and returns hashed path', function () {
    $entry = bassetInstance()->buildCacheEntry('test-block.js');
    $code = 'console.log("hello world");';

    $path = $entry->getPathOnDiskHashed($code);

    // The returned path should include an 8-char hash before .js
    expect($path)->toMatch('/^basset\/test-block-\w{8}\.js$/');

    // The hash is embedded in the filename — extract it
    preg_match('/-(\w{8})\.js$/', $path, $m);
    expect($m[1])->toHaveLength(8);
    expect($m[1])->toMatch('/^\w{8}$/');
});

it('getPathOnDiskHashed generates different hashes for different content', function () {
    $entry1 = bassetInstance()->buildCacheEntry('test-block.js');
    $path1 = $entry1->getPathOnDiskHashed('console.log("hello");');

    $entry2 = bassetInstance()->buildCacheEntry('test-block.js');
    $path2 = $entry2->getPathOnDiskHashed('console.log("world");');

    // Different content → different hash → different path
    expect($path1)->not->toBe($path2);
});

it('getPathOnDiskHashed produces same hash for identical content', function () {
    $code = 'console.log("hello");';

    $entry1 = bassetInstance()->buildCacheEntry('test-block.js');
    $path1 = $entry1->getPathOnDiskHashed($code);

    $entry2 = bassetInstance()->buildCacheEntry('test-block.js');
    $path2 = $entry2->getPathOnDiskHashed($code);

    // Same content → same hash → same path
    expect($path1)->toBe($path2);
});

it('getPathOnDiskHashed sets assetDiskPath to the hashed path', function () {
    $entry = bassetInstance()->buildCacheEntry('test-block.js');
    $path = $entry->getPathOnDiskHashed('some code');

    expect($entry->getAssetDiskPath())->toBe($path);
    expect($entry->getAssetDiskPath())->toMatch('/-\w{8}\.js$/');
});
