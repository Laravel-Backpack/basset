<?php

use Backpack\Basset\Facades\Basset;

beforeEach(function () {
    Basset::clearNamedAssets();
});

afterEach(function () {
    Basset::clearNamedAssets();
});

// Helper: mirror the registerBassetAssets dispatch logic for testing
function invokeRegister(array $assets): void {
    foreach ($assets as $key => $value) {
        if (is_string($value)) {
            Basset::map($value, $value);
        } elseif (array_is_list($value) && (! $value || is_string(reset($value)))) {
            foreach ($value as $file) {
                Basset::map($file, $file);
            }
        } else {
            $source = $value['source'] ?? $key;
            $attributes = array_diff_key($value, ['source' => null]);
            Basset::map($key, $source, $attributes);
        }
    }
}

it('registers simple string assets', function () {
    invokeRegister([
        'https://cdn.com/a.css',
        'https://cdn.com/b.css',
    ]);

    $named = Basset::getNamedAssets();
    expect($named)->toHaveKeys(['https://cdn.com/a.css', 'https://cdn.com/b.css']);
    expect($named['https://cdn.com/a.css']['source'])->toBe('https://cdn.com/a.css');
});

it('registers sequential list assets', function () {
    invokeRegister([
        ['https://cdn.com/x.js', 'https://cdn.com/y.js'],
    ]);

    $named = Basset::getNamedAssets();
    expect($named)->toHaveKeys(['https://cdn.com/x.js', 'https://cdn.com/y.js']);
});

it('registers key-as-source with attributes', function () {
    invokeRegister([
        'https://cdn.com/refreshable.css' => ['refresh' => true],
    ]);

    $named = Basset::getNamedAssets();
    expect($named)->toHaveKey('https://cdn.com/refreshable.css');
    expect($named['https://cdn.com/refreshable.css']['source'])->toBe('https://cdn.com/refreshable.css');
    expect($named['https://cdn.com/refreshable.css']['attributes'])->toBe(['refresh' => true]);
});

it('registers named asset with explicit source', function () {
    invokeRegister([
        'my-widget' => ['source' => 'https://cdn.com/widget.js', 'refresh' => true],
    ]);

    $named = Basset::getNamedAssets();
    expect($named)->toHaveKey('my-widget');
    expect($named['my-widget']['source'])->toBe('https://cdn.com/widget.js');
    expect($named['my-widget']['attributes'])->toBe(['refresh' => true]);
});

it('treats numeric-keyed assoc as named asset, not sequential list', function () {
    invokeRegister([
        ['source' => 'https://cdn.com/widget.js', 'refresh' => true],
    ]);

    $named = Basset::getNamedAssets();
    // Key 0, treated as associative because values aren't strings
    expect($named)->toHaveKey(0);
    expect($named[0]['source'])->toBe('https://cdn.com/widget.js');
    expect($named[0]['attributes'])->toBe(['refresh' => true]);
});

it('handles empty array gracefully', function () {
    invokeRegister([]);

    expect(Basset::getNamedAssets())->toBe([]);
});

it('handles mixed formats together', function () {
    invokeRegister([
        'https://cdn.com/simple.js',
        'https://cdn.com/refresher.js' => ['refresh' => true],
        'my-widget' => ['source' => 'https://cdn.com/widget.js'],
        ['https://cdn.com/a.js', 'https://cdn.com/b.js'],
    ]);

    $named = Basset::getNamedAssets();
    expect($named)->toHaveKeys([
        'https://cdn.com/simple.js',
        'https://cdn.com/refresher.js',
        'my-widget',
        'https://cdn.com/a.js',
        'https://cdn.com/b.js',
    ]);
});

it('handles empty sequential array without crash', function () {
    invokeRegister([
        [],
    ]);

    expect(Basset::getNamedAssets())->toBe([]);
});
