<?php

namespace Backpack\Basset;

abstract class Bassets
{
    /**
     * Get the assets that should be published.
     *
     * @return array
     */
    abstract public function assets(): array;
}
