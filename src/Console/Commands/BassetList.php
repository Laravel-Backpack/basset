<?php

namespace Backpack\Basset\Console\Commands;

use Backpack\Basset\Facades\Basset;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Basset Named Assets list command.
 *
 * @property object $output
 */
class BassetList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'basset:list-named {--filter=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the named assets that packages added to the map.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $assets = Basset::getNamedAssets();

        foreach ($assets as $key => $asset) {
            if ($this->option('filter')) {
                $filter = $this->option('filter');
                if (! Str::contains($key, $filter) || ! Str::contains($asset['source'], $filter)) {
                    continue;
                }
            }
            $this->line('<fg=blue> Asset Key: </>'.$key.'</><fg=blue> Asset Source: </>'.$asset['source']);
        }

        if (empty($assets)) {
            $this->line('No named assets listed.');
        }
    }
}
