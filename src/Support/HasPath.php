<?php 

namespace  Backpack\Basset\Support;
use Illuminate\Support\Str;

trait HasPath
{   
     /**
     * Returns the asset path.
     *
     * @return string
     */
    public function getPath(string $asset): string
    {
        return Str::of($this->basePath)
            ->append(str_replace([base_path().'/', base_path(), 'http://', 'https://', '://', '<', '>', ':', '"', '|', "\0", '*', '`', ';', "'", '+'], '', $asset))
            ->before('?')
            ->replace('/\\', '/');
    }
}