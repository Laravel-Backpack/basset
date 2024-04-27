<?php

namespace Backpack\Basset\Helpers;

use Backpack\Basset\Enums\StatusEnum;

class LoadingTime
{
    private float $startTime = 0;
    private float $time = 0;
    private int $calls = 0;

    /**
     * Start a measuring.
     *
     * @return void
     */
    public function start(): void
    {
        $this->startTime = microtime(true);
    }

    /**
     * Stop a measuring.
     *
     * @return StatusEnum
     */
    public function finish(StatusEnum $result): StatusEnum
    {
        $this->calls++;
        $this->time += microtime(true) - $this->startTime;

        return $result;
    }

    /**
     * Get total time measured in milliseconds.
     *
     * @return float
     */
    public function getLoadingTime(): float
    {
        return $this->time * 1000;
    }

    /**
     * Get total runs.
     *
     * @return int
     */
    public function getTotalCalls(): int
    {
        return $this->calls;
    }
}
