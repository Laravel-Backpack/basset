<?php

namespace Backpack\Basset\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Basset Cache command.
 *
 * @property object $output
 */
class BassetInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'basset:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Backpack Basset.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->components->info('Installing Basset');

        // create symlink
        $this->createSymLink();

        // check if artisan storage:link command exists
        $this->addComposerCommand();

        $this->newLine();
        $this->info('  Done');
    }

    /**
     * Create symlink logic
     *
     * @return void
     */
    private function createSymLink(): void
    {
        $message = 'Creating symlink';

        if (file_exists(public_path('storage'))) {
            $this->components->twoColumnDetail($message, '<fg=yellow;options=bold>SKIPPING</>');
            return;
        }

        $this->call('storage:link');
        $this->components->twoColumnDetail($message, '<fg=green;options=bold>DONE</>');
    }

    /**
     * Add storage:link command to composer logic
     *
     * @return void
     */
    private function addComposerCommand(): void
    {
        $message = 'Adding storage:link command to composer';

        if (Str::of(file_get_contents('composer.json'))->contains('php artisan storage:link')) {
            $this->components->twoColumnDetail($message, '<fg=yellow;options=bold>SKIPPING</>');
            return;
        }

        if ($this->components->confirm('Do you wish to add symlink creation command to composer.json post install script?', true)) {
            $this->components->task($message, function () {
                Process::run('composer config scripts.post-install-cmd.-1 "php artisan storage:link"');
            });
        }
    }
}
