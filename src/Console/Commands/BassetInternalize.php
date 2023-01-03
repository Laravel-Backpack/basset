<?php

namespace DigitallyHappy\Assets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
    protected $description = 'Cache all the assets under the bassets blade directive';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $this->line('Looking for assets under the following directories:');
        $directories = collect(config('digitallyhappy.assets.view_paths'))
            ->map(function ($dir) {
                return (string) Str::of($dir)->after(base_path())->trim('\\/');
            });

        // Find bassets
        $totalFiles = 0;
        $bassets = $directories
            ->map(function (string $directory) use (&$totalFiles) {
                // Map all blade files
                $files = $this->getBladeFiles($directory);
                $count = count($files);
                $totalFiles += $count;

                $this->line(" - $directory ($count blade files)");

                return $files;
            })
            ->flatten()
            ->map(function (string $file) {
                // Map all bassets
                $content = file_get_contents($file);
                preg_match_all('/@basset\([\"\'](.+)[\"\']\)/', $content, $matches);

                return $matches[1] ?? [];
            })
            ->flatten();

        $totalBassets = count($bassets);
        if (! $totalBassets) {
            $this->line('No bassets found.');

            return;
        }

        $this->newLine();
        $this->line("Found $totalBassets bassets out of $totalFiles blade files.");

        $bar = $this->output->createProgressBar($totalBassets);
        $bar->start();

        // Cache the bassets
        $bassets->each(function ($basset, $i) use ($bar) {
            $result = app('assets')->basset($basset, false);

            if ($this->getOutput()->isVerbose()) {
                $this->line(str_pad($i, 3, ' ', STR_PAD_LEFT)." $basset");
                $this->line("    $result");
                $this->newLine();
            } else {
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Done');
    }

    /**
     * Gets all blade files in a directory recursively.
     *
     * @param  string  $path
     * @return array
     */
    private function getBladeFiles(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        $files = [];
        foreach ($rii as $file) {
            if (! $file->isDir() && str_ends_with($file, '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
