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

    private array $scriptAttributes = [];

    public function __construct()
    {
        $this->nonce = config('backpack.basset.nonce', null);
        $this->cachebusting = '?'.substr(md5(base_path('composer.lock')), 0, 12);
        $this->useRelativePaths = config('backpack.basset.relative_paths', true);
        $this->scriptAttributes = config('backpack.basset.script_attributes', []);

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
     * @return void
     */
    public function write(CacheEntry $asset): void
    {
        $filePath = $asset->getOutputDiskPath();
        $extension = (string) Str::of($filePath)->afterLast('.');

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
            'src' => $this->assetPath($filePath),
            'args' => $this->prepareAttributes($asset->getAttributes()),
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
        $dev = config('backpack.basset.dev_mode', false);

        $asset = Str::of(asset($path.($dev ? '' : $this->cachebusting)));

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
    /**
     * Inject the configured script_attributes into every <script> tag found in a
     * raw code block (used when Basset falls back to echoing inline code directly).
     */
    public function injectToBlock(string $code): string
    {
        if (empty($this->scriptAttributes)) {
            return $code;
        }

        $attrs = $this->prepareAttributes();

        return preg_replace('/<script\b/i', '<script'.$attrs, $code);
    }

    private function prepareAttributes(array $attributes = []): string
    {
        // Merge global script_attributes as defaults; per-asset attributes take precedence.
        $attributes = array_merge($this->scriptAttributes, $attributes);

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
