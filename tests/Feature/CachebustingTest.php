<?php

use Backpack\Basset\Helpers\FileOutput;
use Illuminate\Support\Facades\File;

it('falls back to a stable cachebusting string when composer.lock is missing', function () {
    // the testbench skeleton doesn't ship a composer.lock
    expect(File::exists(base_path('composer.lock')))->toBeFalse();

    $expected = '?'.substr(md5(base_path('composer.lock')), 0, 12);

    expect((new FileOutput())->assetPath('basset/app.js'))->toEndWith($expected);
});

it('changes the cachebusting string when composer.lock changes', function () {
    $composerLock = base_path('composer.lock');

    try {
        File::put($composerLock, '{"packages": []}');
        $before = (new FileOutput())->assetPath('basset/app.js');

        File::put($composerLock, '{"packages": [{"name": "vendor/package"}]}');
        $after = (new FileOutput())->assetPath('basset/app.js');

        expect($before)->toEndWith('?'.substr(md5('{"packages": []}'), 0, 12))
            ->and($after)->not->toBe($before);
    } finally {
        File::delete($composerLock);
    }
});
