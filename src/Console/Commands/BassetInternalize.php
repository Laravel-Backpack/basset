<?php

namespace DigitallyHappy\Assets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Basset Internalize command.
 *
 * @property object $output
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
    protected $description = 'Cache all the assets under the bassets blade directive';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $starttime = microtime(true);

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
                $files = $this->getBladeFiles(base_path($directory));
                $count = count($files);
                $totalFiles += $count;

                $this->line(" - $directory ($count blade files)");

                return $files;
            })
            ->flatten()
            ->flatMap(function (string $file) {
                // Map all bassets
                $content = file_get_contents($file);
                preg_match_all('/@(bassetArchive|bassetDirectory)\([\"\'](.+?)[\"\'](?:,\s?[\"\'](.+?)?[\"\'])?/', $content, $matches);

                return collect($matches[2])->map(fn (string $entry, int $i) => [$entry, $matches[1][$i], $matches[3][$i]]);
            });

        $totalBassets = count($bassets);
        if (! $totalBassets) {
            $this->line('No bassets found.');

            return;
        }

        $this->newLine();
        $this->line("Found $totalBassets assets in $totalFiles blade files. Internalizing:");

        $bar = $this->output->createProgressBar($totalBassets);
        $bar->start();

        // Cache the bassets
        $bassets->eachSpread(function (string $basset, string $type, string $arg, int $i) use ($bar) {

            // Force output of basset to be false
            if ($type === 'basset') {
                $arg = false;
            }

            $result = app('assets')->{$type}($basset, $arg)->value;

            if ($this->getOutput()->isVerbose()) {
                $this->line(str_pad($i + 1, 3, ' ', STR_PAD_LEFT)." $basset");
                $this->line("    $result");
                $this->newLine();
            } else {
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf('Done in %.2fs', microtime(true) - $starttime));
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
