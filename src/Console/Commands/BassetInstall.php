<?php

namespace Backpack\Basset\Console\Commands;

use Exception;
use Illuminate\Console\Command;
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
    protected $signature = 'basset:install {--no-check} {--git-ignore} : As the name says, `basset:check` will not run.';

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

        if (! $this->option('no-check')) {
            $this->checkBasset();
        }

        // add basset folder to .gitignore
        if ($this->option('git-ignore')) {
            $this->addGitIgnore();
        }

        $this->addComposerCommands();

        $this->newLine();
        $this->info('  Done');
    }

    private function addGitIgnore()
    {
        $publicPath = public_path(config('backpack.basset.path'));
        $basePath = base_path();

        $publicPath = Str::of($publicPath)->after($basePath)->replace('\\', '/')->start('/')->value();

        $message = 'Adding '.$publicPath.' to .gitignore';

        if (! file_exists(base_path('.gitignore'))) {
            $this->components->task($message, function () use ($publicPath) {
                file_put_contents(base_path('.gitignore'), $publicPath);
            });

            return;
        }

        if (Str::of(file_get_contents(base_path('.gitignore')))->contains($publicPath)) {
            $this->components->twoColumnDetail($message, '<fg=yellow;options=bold>BASSET PATH ALREADY EXISTS ON GITIGNORE</>');

            return;
        }

        $this->components->task($message, function () use ($publicPath) {
            file_put_contents(base_path('.gitignore'), $publicPath, FILE_APPEND);
        });
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
    private function addComposerCommands(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        $composerJsonUpdate = false;

        $message = 'Adding basset:cache to composer.json post-install script';

        if (isset($composer['scripts']['post-install-cmd']) && in_array('@php artisan basset:cache', $composer['scripts']['post-install-cmd'])) {
            $this->components->twoColumnDetail($message, '<fg=yellow;options=bold>ALREADY EXISTS</>');
        } else {
            $composerJsonUpdate = true;
            $composer['scripts']['post-install-cmd'][] = '@php artisan basset:cache';
        }

        $message = 'Adding basset:cache to composer.json post-update script';

        if (isset($composer['scripts']['post-update-cmd']) && in_array('@php artisan basset:cache', $composer['scripts']['post-update-cmd'])) {
            $this->components->twoColumnDetail($message, '<fg=yellow;options=bold>ALREADY EXISTS</>');
        } else {
            $composerJsonUpdate = true;
            $composer['scripts']['post-update-cmd'][] = '@php artisan basset:cache';
        }

        if ($composerJsonUpdate) {
            file_put_contents(base_path('composer.json'), json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}
