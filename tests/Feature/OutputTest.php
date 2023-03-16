<?php

it('echoes the correct html tag', function ($asset, $tag) {
    basset($asset);

    $this->expectOutputRegex("/$tag/");
})->with('htmlTags');

it('echoes the attributes', function ($asset) {
    basset($asset, true, [
        'async' => true,
        'type' => 'module',
    ]);

    $this->expectOutputRegex('/<script .+ async type="module"/');
})->with('cdn');
