<?php

use Backpack\Basset\Enums\StatusEnum;
use Backpack\Basset\Facades\Basset;


if (!function_exists('basset')) {
  function basset($asset, ...$parameters)
  {
    $status = Basset::basset($asset, ...$parameters);

    if($status == StatusEnum::DISABLED)
      return $asset;

    return Basset::getUrl($asset);
  }
}
