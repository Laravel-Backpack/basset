<?php

use Backpack\Basset\Helpers\FileOutput;

// ─── FileOutput::injectToBlock() unit tests ───────────────────────────────────

it('injectToBlock returns code unchanged when script_attributes is empty', function () {
    $output = new FileOutput();
    $code = '<script>alert("hello")</script>';

    expect($output->injectToBlock($code))->toBe($code);
});

it('injectToBlock injects a valued attribute into a script tag', function () {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false']]);
    $output = new FileOutput();

    expect($output->injectToBlock('<script>fn()</script>'))
        ->toBe('<script data-cfasync="false">fn()</script>');
});

it('injectToBlock injects a boolean attribute into a script tag', function () {
    config(['backpack.basset.script_attributes' => ['defer' => true]]);
    $output = new FileOutput();

    expect($output->injectToBlock('<script>fn()</script>'))
        ->toBe('<script defer>fn()</script>');
});

it('injectToBlock injects an empty-value attribute as a boolean attribute', function () {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => '']]);
    $output = new FileOutput();

    expect($output->injectToBlock('<script>fn()</script>'))
        ->toBe('<script data-cfasync>fn()</script>');
});

it('injectToBlock injects multiple attributes into a script tag', function () {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false', 'defer' => true]]);
    $output = new FileOutput();

    expect($output->injectToBlock('<script>fn()</script>'))
        ->toBe('<script data-cfasync="false" defer>fn()</script>');
});

it('injectToBlock injects into every script tag when multiple are present in the block', function () {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false']]);
    $output = new FileOutput();

    $result = $output->injectToBlock('<script>fn1()</script><script>fn2()</script>');

    expect($result)->toBe('<script data-cfasync="false">fn1()</script><script data-cfasync="false">fn2()</script>');
});

it('injectToBlock does not modify style tags', function () {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false']]);
    $output = new FileOutput();
    $code = '<style>body { color: red; }</style>';

    expect($output->injectToBlock($code))->toBe($code);
});

it('injectToBlock is case insensitive for the script tag name', function () {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false']]);
    $output = new FileOutput();

    $result = $output->injectToBlock('<SCRIPT>fn()</SCRIPT>');

    // preg_replace uses a literal replacement, so the matched tag is normalised to lowercase
    expect($result)->toContain('<script data-cfasync="false">');
});

it('injectToBlock preserves existing attributes on the script tag', function () {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false']]);
    $output = new FileOutput();

    $result = $output->injectToBlock('<script async type="module">fn()</script>');

    expect($result)->toBe('<script data-cfasync="false" async type="module">fn()</script>');
});

// ─── write() integration tests ────────────────────────────────────────────────

it('adds script_attributes to the script src tag for cdn assets', function ($asset) {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false']]);

    ob_start();
    bassetInstance($asset);
    $html = ob_get_clean();

    expect($html)->toContain('data-cfasync="false"');
})->with('cdn');

it('per-asset attributes override matching global script_attributes', function ($asset) {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false']]);

    ob_start();
    bassetInstance($asset, true, ['data-cfasync' => 'true']);
    $html = ob_get_clean();

    expect($html)
        ->toContain('data-cfasync="true"')
        ->not->toContain('data-cfasync="false"');
})->with('cdn');

it('per-asset attributes are merged with global script_attributes when keys differ', function ($asset) {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false']]);

    ob_start();
    bassetInstance($asset, true, ['crossorigin' => 'anonymous']);
    $html = ob_get_clean();

    expect($html)
        ->toContain('data-cfasync="false"')
        ->toContain('crossorigin="anonymous"');
})->with('cdn');

it('nonce and script_attributes both appear on the script src tag', function ($asset) {
    config([
        'backpack.basset.script_attributes' => ['data-cfasync' => 'false'],
        'backpack.basset.nonce' => 'test-nonce-123',
    ]);

    ob_start();
    bassetInstance($asset);
    $html = ob_get_clean();

    expect($html)
        ->toContain('data-cfasync="false"')
        ->toContain('nonce="test-nonce-123"');
})->with('cdn');

// ─── bassetBlock() echo paths ─────────────────────────────────────────────────

it('injects script_attributes when echoing a block in dev mode', function () {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false']]);
    bassetInstance()->setDevMode(true);

    ob_start();
    bassetInstance()->bassetBlock('test-asset.js', '<script>fn()</script>');
    $html = ob_get_clean();

    expect($html)->toContain('data-cfasync="false"');
});

it('injects script_attributes when echoing a block with cache disabled', function () {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false']]);

    ob_start();
    bassetInstance()->bassetBlock('test-asset.js', '<script>fn()</script>', true, false);
    $html = ob_get_clean();

    expect($html)->toContain('data-cfasync="false"');
});

it('does not inject script_attributes into style blocks in dev mode', function () {
    config(['backpack.basset.script_attributes' => ['data-cfasync' => 'false']]);
    bassetInstance()->setDevMode(true);

    $cssCode = '<style>body { color: red; }</style>';

    ob_start();
    bassetInstance()->bassetBlock('test-asset.css', $cssCode);
    $html = ob_get_clean();

    expect($html)
        ->toBe($cssCode)
        ->not->toContain('data-cfasync');
});
