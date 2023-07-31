<?php

namespace Backpack\Basset\Console\Commands;

use Backpack\Basset\BassetManager;
use Backpack\Basset\Enums\StatusEnum;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Basset Cache command.
 *
 * @property object $output
 */
class BassetCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'basset:check
        {--installing} : Does this call comes from installing command.';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Backpack Basset installation.';

    private string $filepath;
    private BassetManager $basset;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $message = '';
        if (! $this->option('installing')) {
            $this->components->info('Checking Backpack Basset installation');
        }

        try {
            // init check
            $message = 'Initializing basset check';
            $this->init();
            $this->components->twoColumnDetail($message, '<fg=green;options=bold>DONE</>');

            // checking storage
            $message = 'Checking cache storage';
            $this->testCache();
            $this->components->twoColumnDetail($message, '<fg=green;options=bold>DONE</>');

            // fetching a basset
            $message = 'Fetching a basset';
            $this->testFetch();
            $this->components->twoColumnDetail($message, '<fg=green;options=bold>DONE</>');

            // clear temporary file
            File::delete($this->filepath);
        } catch (Exception $e) {
            $this->components->twoColumnDetail($message, '<fg=red;options=bold>ERROR</>');
            $this->newLine();
            $this->components->error($e->getMessage());
            $this->line('  <fg=gray>│ Backpack Basset failed to check it\'s working properly.</>');
            $this->line('  <fg=gray>│</>');
            $this->line('  <fg=gray>│ This may be due to multiple issues. Please ensure:</>');
            $this->line('  <fg=gray>│  1) APP_URL is correctly set in the <fg=white>.env</> file.</>');
            $this->line('  <fg=gray>│  2) Your server is running and accessible at <fg=white>'.url('').'</>.</>');
            $this->line('  <fg=gray>│  3) Your disk is properly configured in <fg=white>config/filesystems.php</>.</>');
            $this->line('  <fg=gray>│  4) The storage symlink exists and is valid (public/storage).</>');
            $this->line('  <fg=gray>│</>');
            $this->line('  <fg=gray>│ For more information and solutions, please visit the Backpack Basset FAQ at:</>');
            $this->line('  <fg=gray>│ https://github.com/laravel-backpack/basset#faq</>');
            $this->newLine();
            exit(1);
        }

        if (! $this->option('installing')) {
            $this->newLine();
        }
    }

    /**
     * Initialize the test.
     *
     * @return void
     */
    private function init(): void
    {
        $this->basset = app('basset');

        // create a local temporary file
        $path = storage_path('app/tmp/');
        $file = 'backpack-test.js';
        $this->filepath = $path.$file;

        File::ensureDirectoryExists($path);
        File::put($this->filepath, 'test');

        if (! File::exists($this->filepath)) {
            throw new Exception('Error accessing the filesystem, the check can not run.');
        }
    }

    /**
     * Test cache the asset.
     *
     * @return void
     */
    private function testCache(): void
    {
        // cache it with basset
        $result = $this->basset->basset($this->filepath, false);

        if (! in_array($result, [StatusEnum::CACHED, StatusEnum::IN_CACHE])) {
            throw new Exception('Error caching the file.');
        }
    }

    /**
     * Test fetch the asset with Http.
     *
     * @return void
     */
    private function testFetch(): void
    {
        // cache it with basset
        $url = $this->basset->getUrl($this->filepath);

        if (! str_contains($url, '://')) {
            $url = url($url);
        }

        $result = Http::get($url);

        if ($result->body() !== 'test') {
            throw new Exception('Error fetching the file.');
        }
    }
}
