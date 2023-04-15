<?php

namespace Backpack\Basset\Traits;

use Backpack\Basset\Enums\StatusEnum;

trait TimeMeasureTrait
{
    private $startTime = 0;
    private $loadingTime = 0;
    private $calls = 0;

    /**
     * Start a measuring
     *
     * @return void
     */
    public function startRequest(): void
    {
        $this->startTime = microtime(true);
    }

    /**
     * Stop a measuring
     *
     * @return StatusEnum
     */
    public function finishRequest(StatusEnum $result): StatusEnum
    {
        $this->calls++;
        $this->loadingTime += microtime(true) - $this->startTime;

        return $result;
    }

    /**
     * Get total time measured
     *
     * @return integer
     */
    public function getLoadingTime(): float
    {
        return $this->loadingTime;
    }

    /**
     * Get total runs
     *
     * @return integer
     */
    public function getTotalCalls(): float
    {
        return $this->calls;
    }
}
