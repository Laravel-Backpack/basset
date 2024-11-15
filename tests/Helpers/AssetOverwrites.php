<?php

namespace Backpack\Basset\Tests\Helpers;

use Backpack\Basset\Facades\Basset;

class AssetOverwrites implements \Backpack\Basset\AssetOverwrite
{
    public function assets(): void
    {
        Basset::map('react', 'https://unpkg.com/backpack@5/dist/script.production.min.js', ['integrity' => 'something-else']);
    }
}
