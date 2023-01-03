<?php

namespace DigitallyHappy\Assets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BassetClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'basset:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the bassets cache';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $dir = config('digitallyhappy.assets.cache_directory');

        $this->line('Clearing "'.Str::of($dir)->after(base_path())->trim('\\/').'"');

        // Allow only dirs inside base folder.
        if (! Str::of($dir)->startsWith(base_path())) {
            $this->error('Only folders inside base path can be used.');
        }

        $this->removeDirectory($dir);
        mkdir($dir);

        $this->info('Done');
    }

    /**
     * Remove Directory.
     *
     * @param  string  $path
     * @return void
     */
    private function removeDirectory(string $path): void
    {
        $files = glob($path.'/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}
