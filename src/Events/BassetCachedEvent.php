<?php

namespace Backpack\Basset\Events;

use Illuminate\Foundation\Events\Dispatchable;

class BassetCachedEvent
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $asset,
    ) {
    }
}
