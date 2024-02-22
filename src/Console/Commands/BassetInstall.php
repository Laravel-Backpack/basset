<?php

namespace Backpack\Basset\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

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
    protected $signature = 'basset:install {--no-check} : As the name says, `basset:check` will not run.';

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

        if (! $this->option('no-check')) {
            $this->checkBasset();
        }

        // check if artisan storage:link command exists
        $this->addComposerCommand();

        $this->newLine();
        $this->info('  Done');
    }

    /**
     * Create symlink logic.
     *
     * @return void
     */
    private function createSymLink(): void
    {
        $message = 'Creating symlink';

        try {
            $this->callSilent('storage:link');
            $this->components->twoColumnDetail($message, '<fg=green;options=bold>DONE</>');
        } catch (Exception $e) {
            $this->components->twoColumnDetail($message, '<fg=red;options=bold>ERROR</>');
            $this->line('  <fg=gray>│ '.$e->getMessage().'</>');
            $this->newLine();
        }
    }

    /**
     * Check if basset works.
     *
     * @return void
     */
    private function checkBasset(): void
    {
        $message = 'Check Basset';

        try {
            $this->call('basset:check', ['--installing' => true]);
            $this->components->twoColumnDetail($message, '<fg=green;options=bold>DONE</>');
        } catch (Exception $e) {
            $this->components->twoColumnDetail($message, '<fg=red;options=bold>ERROR</>');
        }
    }

    /**
     * Add storage:link command to composer logic.
     *
     * @return void
     */
    private function addComposerCommand(): void
    {
        $message = 'Adding storage:link command to composer.json';

        if (Str::of(file_get_contents('composer.json'))->contains('php artisan storage:link')) {
            $this->components->twoColumnDetail($message, '<fg=yellow;options=bold>ALREADY EXISTED</>');

            return;
        }

        if ($this->components->confirm('You will need to run `php artisan storage:link` on every server you deploy the app to. Do you wish to add that command to composer.json\' post-install-script, to make that automatic?', true)) {
            $this->components->task($message, function () {
                $process = new Process(['composer', 'config', 'scripts.post-install-cmd.-1', '@php artisan storage:link --quiet']);
                $process->run();
            });
        }
    }
}
