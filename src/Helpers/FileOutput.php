<?php

namespace Backpack\Basset\Helpers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FileOutput
{
    private ?string $nonce;

    private string $cachebusting;

    private bool $useRelativePaths = true;

    private array $templates = [];

    public function __construct()
    {
        $this->nonce = config('backpack.basset.nonce', null);
        $this->cachebusting = '?'.substr(md5(base_path('composer.lock')), 0, 12);
        $this->useRelativePaths = config('backpack.basset.relative_paths', true);

        // load all templates
        $templates = File::allFiles(realpath(__DIR__.'/../resources/views'));
        foreach ($templates as $template) {
            $extension = str_replace('.blade.php', '', $template->getFilename());
            $this->templates[$extension] = File::get($template);
        }
    }

    /**
     * Outputs a file depending on its type.
     *
     * @param  string  $path
     * @param  array  $attributes
     * @return void
     */
    public function write(string $path, array $attributes = []): void
    {
        $extension = (string) Str::of($path)->afterLast('.');

        // map extensions
        $file = match ($extension) {
            'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg' => 'img',
            'mp3', 'ogg', 'wav', 'mp4', 'webm', 'avi' => 'source',
            'pdf' => 'object',
            'vtt' => 'track',
            default => $extension
        };

        $template = $this->templates[$file] ?? null;

        if (! $template) {
            return;
        }

        echo Blade::render($template, [
            'src' => $this->assetPath($path),
            'args' => $this->prepareAttributes($attributes),
        ]);
    }

    /**
     * Generates the asset path.
     *
     * @param  string  $path
     * @return string
     */
    public function assetPath(string $path): string
    {
        $asset = Str::isUrl($path) ? Str::of(asset($path)) : Str::of(asset($path.$this->cachebusting));

        if ($this->useRelativePaths && $asset->startsWith(url(''))) {
            $asset = $asset->after('//')->after('/')->start('/');
        }

        return $asset->value();
    }

    /**
     * Prepares attributes to be added to the script/style dom element.
     *
     * @param  array  $attributes
     * @return string
     */
    private function prepareAttributes(array $attributes = []): string
    {
        if ($this->nonce) {
            $attributes['nonce'] ??= $this->nonce;
        }

        $args = '';
        foreach ($attributes as $key => $value) {
            $args .= " $key".($value === true || empty($value) ? '' : "=\"$value\"");
        }

        return $args;
    }
}
