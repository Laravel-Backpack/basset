<?php

use Backpack\Basset\Enums\StatusEnum;
use Backpack\Basset\Facades\Basset;

if (! function_exists('basset')) {
    function basset(string $asset): string
    {
        // cache the asset without output
        $status = Basset::basset($asset, false);

        if (in_array($status, [StatusEnum::DISABLED, StatusEnum::INVALID])) {
            return $asset;
        }

        return Basset::getUrl($asset);
    }
}
