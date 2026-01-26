<?php

namespace Backpack\Basset\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Basset Clear command.
 *
 * @property object $output
 */
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
    protected $description = 'Clear the basset cache';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        /** @var FilesystemAdapter */
        $disk = Storage::disk(config('backpack.basset.disk'));
        $path = config('backpack.basset.path');
        $pathRelative = Str::of($disk->path($path))->replace(base_path(), '')->replace('\\', '/');

        $this->line("Clearing basset '$pathRelative'");

        self::clearBassetCache();

        $this->info('Done');
    }

    /**
     * Clear the basset cache.
     *
     * @return void
     */
    public static function clearBassetCache(): void
    {
        /** @var FilesystemAdapter */
        $disk = Storage::disk(config('backpack.basset.disk'));
        $path = config('backpack.basset.path');

        $disk->deleteDirectory($path);
        $disk->makeDirectory($path);
        $disk->delete('.basset');
    }
}
