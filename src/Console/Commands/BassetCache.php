<?php

namespace Backpack\Basset\Console\Commands;

use Backpack\Basset\Enums\StatusEnum;
use Backpack\Basset\Facades\Basset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Basset Cache command.
 *
 * @property object $output
 */
class BassetCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'basset:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache all the assets using the basset blade directive and update the cache map.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $internalizedAssets = [];
        $notInternalizedAssets = [];

        $starttime = microtime(true);

        $viewPaths = Basset::getViewPaths();

        $this->line('Looking for bassets under the following directories:');

        // Find bassets
        $totalFiles = 0;
        $bassets = collect($viewPaths)
            ->map(function (string $path) use (&$totalFiles) {
                // Map all blade files
                $files = $this->getBladeFiles($path);
                $count = count($files);
                $totalFiles += $count;

                $relativePath = Str::of($path)->after(base_path())->trim('\\/');

                $this->line(" - $relativePath ($count blade files)");

                return $files;
            })
            ->flatten()
            ->flatMap(function (string $file) {
                // Map all bassets
                $content = File::get($file);
                preg_match_all('/(basset|@bassetArchive|@bassetDirectory)\((.+)\)/', $content, $matches);

                $matches[2] = collect($matches[2])
                    ->map(fn ($match) => collect(explode(',', $match))
                            ->map(function ($arg) {
                                try {
                                    return eval("return $arg;");
                                } catch (Throwable $th) {
                                    return false;
                                }
                            })
                            ->toArray()
                    );

                return collect($matches[1])->map(fn (string $type, int $i) => [$type, $matches[2][$i]]);
            });
        $totalBassets = count($bassets);
        if (! $totalBassets) {
            $this->line('No bassets found.');

            return;
        }

        $this->newLine();
        $this->line("Found $totalBassets bassets in $totalFiles blade files. Caching:");

        $bar = $this->output->createProgressBar($totalBassets);
        $bar->start();
        // Cache the bassets
        $bassets->eachSpread(function (string $type, array $args, int $i) use ($bar, &$internalizedAssets, &$notInternalizedAssets) {
            if ($args[0] === false) {
                return;
            }
            $type = Str::of($type)->after('@')->before('(')->value();
            // Force output of basset to be false
            if ($type === 'basset') {
                $args[1] = false;
            }

            try {
                if (in_array($type, ['basset', 'bassetArchive', 'bassetDirectory', 'bassetBlock'])) {
                    $result = Basset::{$type}(...$args)->value;
                    if ($result !== StatusEnum::INVALID->value) {
                        $internalizedAssets[] = $args[0];
                    } else {
                        $notInternalizedAssets[] = $args[0];
                    }
                } else {
                    throw new \Exception('Invalid basset type');
                }
            } catch (Throwable $th) {
                $result = StatusEnum::INVALID->value;
                $notInternalizedAssets[] = $args[0];
            }

            if ($this->getOutput()->isVerbose()) {
                $this->line(str_pad(strval($i + 1), 3, ' ', STR_PAD_LEFT).' '.$args[0]);
                $this->line("    $result");
                $this->newLine();
            } else {
                $bar->advance();
            }
        });

        // we will now loop through the bassets that are in the named map, and internalize any that our script hasn't internalized yet
        $namedAssets = Basset::getNamedAssets();

        // get the named assets that are not internalized yet
        $namedAssets = collect($namedAssets)
            ->filter(function ($asset, $id) use ($internalizedAssets) {
                return ! in_array($id, $internalizedAssets);
            });

        foreach ($namedAssets as $id => $asset) {
            $result = Basset::basset($id, false)->value;
            if ($result !== StatusEnum::INVALID->value) {
                $internalizedAssets[] = $id;
            } else {
                $notInternalizedAssets[] = $id;
            }
        }

        $notInternalizedAssets = implode(', ', array_unique($notInternalizedAssets));

        // Save the cache map
        Basset::cacheMap()->save();

        $bar->finish();

        if (! empty($notInternalizedAssets)) {
            $this->newLine(2);
            $this->line('Failed to cache: '.$notInternalizedAssets);
        }

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
