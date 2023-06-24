<?php

namespace Backpack\Basset\Console\Commands;

use Illuminate\Console\Command;

/**
 * Basset Internalize command.
 *
 * @property object $output
 *
 * @deprecated 0.16.0
 */
class BassetInternalize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'basset:internalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache all the assets under the basset blade directive';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->call('basset:cache');
    }
}
