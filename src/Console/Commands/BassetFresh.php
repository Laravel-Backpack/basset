<?php

namespace Backpack\Basset\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

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
        Process::pipe([
            'php artisan basset:clear',
            'php artisan basset:cache',
        ]);
    }
}
