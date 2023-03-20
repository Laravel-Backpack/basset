<?php

namespace Backpack\Basset\Console\Commands;

use Backpack\Basset\Enums\StatusEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

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
    protected $description = 'Cache all the assets under the basset blade directive';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $starttime = microtime(true);

        $this->line('Looking for bassets under the following directories:');
        $directories = collect(config('backpack.basset.view_paths'))
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
                $content = File::get($file);
                preg_match_all('/@(basset|bassetArchive|bassetDirectory)\((.+)\)/', $content, $matches);

                $matches[2] = collect($matches[2])
                    ->map(fn($match) =>
                        collect(explode(',', $match))
                            ->map(function ($arg) {
                                try {
                                    return eval("return $arg;");
                                } catch (Throwable $th) {
                                    return false;
                                }
                            })
                            ->toArray()
                    );

                return collect($matches[1])->map(fn(string $type, int $i) => [$type, $matches[2][$i]]);
            });

        $totalBassets = count($bassets);
        if (! $totalBassets) {
            $this->line('No bassets found.');

            return;
        }

        $this->newLine();
        $this->line("Found $totalBassets bassets in $totalFiles blade files. Internalizing:");

        $bar = $this->output->createProgressBar($totalBassets);
        $bar->start();

        // Cache the bassets
        $bassets->eachSpread(function (string $type, array $args, int $i) use ($bar) {

            // Force output of basset to be false
            if ($type === 'basset') {
                $args[1] = false;
            }

            try {
                $result = app('basset')->{$type}(...$args)->value;
            } catch (Throwable $th) {
                $result = StatusEnum::INVALID->value;
            }

            if ($this->getOutput()->isVerbose()) {
                $this->line(str_pad($i + 1, 3, ' ', STR_PAD_LEFT).' '.$args[0]);
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
