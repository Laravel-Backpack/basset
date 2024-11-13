<?php

namespace Backpack\Basset\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Backpack\Basset\Facades\Basset;

/**
 * Basset Named Assets list command.
 *
 * @property object $output
 */
class BassetNamedAssetsList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'basset:named-assets {--filter=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the assets that packages added to the map.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $assets = Basset::getNamedAssets();

        foreach($assets as $key => $asset) {
            if ($this->option('filter')) {
                $filter = $this->option('filter');
                if (!Str::contains($key, $filter) || !Str::contains($asset['source'], $filter)) {
                    continue;
                }
            }
            $this->line('<fg=blue> Asset Key: </>'. $key . '</><fg=blue> Asset Source: </>' . $asset['source']);
        }
    }
}
