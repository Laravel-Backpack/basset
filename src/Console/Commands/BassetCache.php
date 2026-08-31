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
    protected $signature = 'basset:cache
                            {--stale : Remove cached assets that are no longer referenced in blade files}';

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
                $bassets = collect();

                preg_match_all('/(basset|@bassetArchive|@bassetDirectory)\(/', $content, $matches, PREG_OFFSET_CAPTURE);

                foreach ($matches[1] as $i => $typeMatch) {
                    $type = $typeMatch[0];
                    $argsString = $this->extractBalancedArguments($content, $typeMatch[1] + strlen($type));

                    if ($argsString === null) {
                        continue;
                    }

                    $args = $this->parseBassetArguments($argsString);
                    $bassets->push([ltrim($type, '@'), $args]);
                }

                preg_match_all('/@bassetBlock\((.+?)\)(.*?)@endBassetBlock/si', $content, $matches);
                foreach ($matches[1] as $i => $argsString) {
                    $args = $this->parseBassetArguments($argsString);
                    array_splice($args, 1, 0, [$matches[2][$i]]);
                    $bassets->push(['bassetBlock', $args]);
                }

                return $bassets;
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

            if ($type === 'bassetBlock') {
                $args[2] = false;
            }

            // Skip if the asset is a URL already cached (URL identity = content identity).
            // Local files are never skipped — re-copying is cheap and content may have changed.
            // Only active when cache_map_comparison is enabled (opt-in).
            $attributes = is_array($args[2] ?? null) ? $args[2] : [];
            if (config('backpack.basset.cache_map_comparison') && $this->shouldSkipCachedAsset($args[0], $attributes)) {
                $internalizedAssets[] = $args[0];

                if ($this->getOutput()->isVerbose()) {
                    $this->line(str_pad(strval($i + 1), 3, ' ', STR_PAD_LEFT).' '.$args[0]);
                    $this->line('    '.StatusEnum::SKIPPED->value);
                    $this->newLine();
                } else {
                    $bar->advance();
                }

                return;
            }

            // When the asset already exists on disk, delete the old copy to force
            // a fresh download/copy. Applies to: local files (when comparison enabled)
            // and URL assets with the 'refresh' attribute.
            if ($type === 'basset') {
                $this->invalidateCachedLocalFile($args[0], $args[2] ?? []);
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
            // Skip if the named asset is already cached (when comparison enabled and no refresh flag)
            $namedAttributes = $asset['attributes'] ?? [];
            if (config('backpack.basset.cache_map_comparison')
                && ! ($namedAttributes['refresh'] ?? false)
                && Basset::isAssetCached($id)) {
                $internalizedAssets[] = $id;
                continue;
            }

            // If refresh is set, invalidate the old cached file to force re-download
            if ($namedAttributes['refresh'] ?? false) {
                $this->invalidateCachedLocalFile($id);
            }

            $result = Basset::basset($id, false)->value;
            if ($result !== StatusEnum::INVALID->value) {
                $internalizedAssets[] = $id;
            } else {
                $notInternalizedAssets[] = $id;
            }
        }

        $notInternalizedAssets = implode(', ', array_unique($notInternalizedAssets));

        // Remove stale assets that are no longer referenced in blade files
        if ($this->option('stale')) {
            $staleCount = $this->cleanStaleAssets($internalizedAssets);
            if ($staleCount > 0) {
                $this->line("Removed $staleCount stale asset(s).");
            }
        }

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
     * Remove a cached file from disk if present, forcing a fresh download/copy.
     * Invalidates when: the 'refresh' attribute is set, OR it's a local file
     * and cache_map_comparison is enabled.
     *
     * @param  string  $asset
     * @return void
     */
    private function invalidateCachedLocalFile(string $asset, array $attributes = []): void
    {
        $entry = Basset::buildCacheEntry($asset);
        $attributes = array_merge($entry->getAttributes(), $attributes);
        $isUrl = Str::isUrl($entry->getAssetPath());

        $shouldInvalidate = ($attributes['refresh'] ?? false)
            || (! $isUrl && config('backpack.basset.cache_map_comparison'));

        if (! $shouldInvalidate) {
            return;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk(config('backpack.basset.disk'));
        if ($entry->existsOnDisk($disk)) {
            $disk->delete($entry->getAssetDiskPath());
        }
    }

    /**
     * Determine if an asset should be skipped (already cached and is a URL).
     * Local files are never skipped — re-copying is cheap and content may have changed.
     *
     * @param  string  $asset
     * @return bool
     */
    private function shouldSkipCachedAsset(string $asset, array $attributes = []): bool
    {
        $entry = Basset::buildCacheEntry($asset);

        // Never skip if the asset has the 'refresh' attribute
        if (($attributes['refresh'] ?? false) || ($entry->getAttributes()['refresh'] ?? false)) {
            return false;
        }

        // Only skip URL assets — they're identified by their URL string
        if (! Str::isUrl($entry->getAssetPath())) {
            return false;
        }

        return Basset::isAssetCached($asset);
    }

    /**
     * Remove cached assets that are no longer referenced in blade files.
     *
     * @param  array  $activeAssets  Asset names found in current blade files
     * @return int Number of stale assets removed
     */
    private function cleanStaleAssets(array $activeAssets): int
    {
        $cacheMap = Basset::cacheMap();
        $allCachedAssets = array_keys($cacheMap->getMap());
        $staleAssets = array_diff($allCachedAssets, $activeAssets);

        $count = 0;
        foreach ($staleAssets as $staleAsset) {
            $entry = Basset::buildCacheEntry($staleAsset);

            // Delete the file from disk
            $disk = $cacheMap->getDisk();
            if ($disk && $disk->exists($entry->getAssetDiskPath())) {
                $disk->delete($entry->getAssetDiskPath());
            }

            // Remove from cache map
            $cacheMap->delete($entry);
            $count++;
        }

        return $count;
    }

    private function extractBalancedArguments(string $content, int $openParenOffset): ?string
    {
        $length = strlen($content);
        $depth = 0;
        $quote = null;

        for ($i = $openParenOffset; $i < $length; $i++) {
            $char = $content[$i];

            // A backslash escapes the next character (handles \', \" and \\,
            // and parity works out for double backslashes).
            if ($char === '\\') {
                $i++;
                continue;
            }

            if ($quote !== null) {
                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                continue;
            }

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;

                if ($depth === 0) {
                    return substr($content, $openParenOffset + 1, $i - $openParenOffset - 1);
                }
            }
        }

        return null;
    }

    /**
     * Parse basset arguments from a string, respecting array boundaries and quotes.
     *
     * @param  string  $argumentString
     * @return array
     */
    private function parseBassetArguments(string $argumentString): array
    {
        $length = strlen($argumentString);
        $arguments = [];
        $current = '';
        $state = [
            'inQuotes' => false,
            'quoteChar' => null,
            'bracketDepth' => 0,
            'parenDepth' => 0,
        ];

        for ($i = 0; $i < $length; $i++) {
            $char = $argumentString[$i];
            $isEscaped = $i > 0 && $argumentString[$i - 1] === '\\';

            if (in_array($char, ['"', "'"], true) && ! $isEscaped) {
                if (! $state['inQuotes']) {
                    $state['inQuotes'] = true;
                    $state['quoteChar'] = $char;
                } elseif ($char === $state['quoteChar']) {
                    $state['inQuotes'] = false;
                    $state['quoteChar'] = null;
                }
                $current .= $char;
                continue;
            }

            if ($state['inQuotes']) {
                $current .= $char;
                continue;
            }

            $state['bracketDepth'] += match ($char) {
                '[' => 1,
                ']' => -1,
                default => 0,
            };

            $state['parenDepth'] += match ($char) {
                '(' => 1,
                ')' => -1,
                default => 0,
            };

            if ($char === ',' && $state['bracketDepth'] === 0 && $state['parenDepth'] === 0) {
                $arguments[] = $this->evaluateArgument(trim($current));
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (($finalArg = trim($current)) !== '') {
            $arguments[] = $this->evaluateArgument($finalArg);
        }

        return $arguments;
    }

    /**
     * Safely evaluate a single argument string.
     *
     * @param  string  $argument
     * @return mixed
     */
    private function evaluateArgument(string $argument): mixed
    {
        if ($argument === '') {
            return false;
        }

        try {
            return eval("return $argument;");
        } catch (Throwable) {
            return false;
        }
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
