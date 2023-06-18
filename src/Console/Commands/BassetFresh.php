<?php

namespace Backpack\Basset\Console\Commands;

use Illuminate\Console\Command;

/**
 * Basset Fresh command.
 *
 * @property object $output
 */
class BassetFresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'basset:fresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear and Cache the basset directory';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->call('basset:clear');
        $this->call('basset:cache');
    }
}
