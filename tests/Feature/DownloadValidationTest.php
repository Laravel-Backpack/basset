<?php

use Backpack\Basset\Enums\StatusEnum;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['backpack.basset.cache_map' => true]);
    config(['backpack.basset.dev_mode' => false]);
    config(['backpack.basset.fetch_timeout' => 5]);
    config(['backpack.basset.fetch_retries' => 3]);
    config(['backpack.basset.fetch_retry_delay' => 0]); // no delay in tests
});

it('downloads asset on successful HTTP response', function () {
    Http::fake([
        'https://example.com/style.css' => Http::response('body { color: red; }', 200, ['Content-Type' => 'text/css']),
    ]);

    $result = bassetInstance('https://example.com/style.css', false);
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk('https://example.com/style.css');

    expect($result)->toBe(StatusEnum::INTERNALIZED);
    disk()->assertExists($diskPath);
    expect(disk()->get($diskPath))->toBe('body { color: red; }');
});

it('returns invalid when HTTP response is 404', function () {
    Http::fake([
        'https://example.com/missing.css' => Http::response('Not Found', 404),
    ]);

    $result = bassetInstance('https://example.com/missing.css', false);
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk('https://example.com/missing.css');

    expect($result)->toBe(StatusEnum::INVALID);
    disk()->assertMissing($diskPath);
});

it('returns invalid when HTTP response is 500', function () {
    Http::fake([
        'https://example.com/error.css' => Http::response('Internal Server Error', 500),
    ]);

    $result = bassetInstance('https://example.com/error.css', false);
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk('https://example.com/error.css');

    expect($result)->toBe(StatusEnum::INVALID);
    disk()->assertMissing($diskPath);
});

it('returns invalid when HTTP request times out', function () {
    Http::fake([
        'https://example.com/slow.css' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out', 0);
        },
    ]);

    $result = bassetInstance('https://example.com/slow.css', false);
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk('https://example.com/slow.css');

    expect($result)->toBe(StatusEnum::INVALID);
    disk()->assertMissing($diskPath);
});

it('does not overwrite cached asset with error response when file exists', function () {
    $assetUrl = 'https://example.com/style.css';
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk($assetUrl);
    $originalContent = 'body { color: blue; }';

    // pre-populate with valid content
    disk()->put($diskPath, $originalContent);

    // now simulate CDN being down
    Http::fake([
        $assetUrl => Http::response('Service Unavailable', 503),
    ]);

    $result = bassetInstance($assetUrl, false);

    // should serve from cache since file exists on disk
    expect($result)->toBe(StatusEnum::IN_CACHE);

    // the original file should NOT have been overwritten with error content
    expect(disk()->get($diskPath))->toBe($originalContent);
});

it('retries on failure and succeeds on subsequent attempt', function () {
    $attempts = 0;

    Http::fake([
        'https://example.com/flaky.css' => function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new \Illuminate\Http\Client\ConnectionException("Attempt $attempts failed", 0);
            }

            return Http::response('body { color: green; }', 200);
        },
    ]);

    $result = bassetInstance('https://example.com/flaky.css', false);
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk('https://example.com/flaky.css');

    expect($result)->toBe(StatusEnum::INTERNALIZED);
    disk()->assertExists($diskPath);
    expect(disk()->get($diskPath))->toBe('body { color: green; }');
    expect($attempts)->toBe(3);
});

it('retries on 5xx and succeeds on retry', function () {
    $attempts = 0;

    Http::fake([
        'https://example.com/recover.css' => function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                return Http::response('Server Error', 503);
            }

            return Http::response('body { color: purple; }', 200);
        },
    ]);

    $result = bassetInstance('https://example.com/recover.css', false);
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk('https://example.com/recover.css');

    expect($result)->toBe(StatusEnum::INTERNALIZED);
    disk()->assertExists($diskPath);
    expect(disk()->get($diskPath))->toBe('body { color: purple; }');
    expect($attempts)->toBe(3);
});

it('gives up after exhausting all retries', function () {
    $attempts = 0;

    Http::fake([
        'https://example.com/down.css' => function () use (&$attempts) {
            $attempts++;

            throw new \Illuminate\Http\Client\ConnectionException("Attempt $attempts failed", 0);
        },
    ]);

    $result = bassetInstance('https://example.com/down.css', false);
    $diskPath = bassetInstance()->assetPathsManager->getPathOnDisk('https://example.com/down.css');

    expect($result)->toBe(StatusEnum::INVALID);
    disk()->assertMissing($diskPath);
    expect($attempts)->toBe(3); // initial + 2 retries = 3 total attempts
});
