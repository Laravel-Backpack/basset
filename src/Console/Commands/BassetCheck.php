<?php

namespace Backpack\Basset\Console\Commands;

use Backpack\Basset\BassetManager;
use Backpack\Basset\Enums\StatusEnum;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Basset Cache command.
 *
 * @property object $output
 */
class BassetCheck extends Command
{
    use \Backpack\Basset\Console\Commands\Helpers\BassetNotWorkingMessage;
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
        } catch (Exception $e) {
            if ($this->option('installing')) {
                throw new Exception($message);
            }
            $this->bassetNotWorkingMessage($message);
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

        // set a local file to run the tests
        $this->filepath = base_path('vendor/backpack/basset/tests/Helpers/basset-test.js');

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

        if (! in_array($result, [StatusEnum::INTERNALIZED, StatusEnum::IN_CACHE])) {
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

        if (! Str::isUrl($url)) {
            $url = url($url);
        }

        $result = Http::get($url);

        if ($result->body() !== 'test') {
            throw new Exception('Error fetching the file.');
        }
    }
}
