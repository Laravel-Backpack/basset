<?php

namespace DigitallyHappy\Assets;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetManager
{
    const STATUS_LOADED = 'Asset was already loaded.';
    const STATUS_INVALID = 'Asset is not in a CDN or local filesystem.';
    const STATUS_IN_CACHE = 'Asset was already in cache.';
    const STATUS_DOWNLOADED = 'Asset downloaded.';
    const STATUS_NO_ACTION = 'Asset was not internalized, falling back to provided path.';

    private $loaded;
    private $disk;
    private $cachebusting;

    public function __construct()
    {
        $this->loaded = [];
        $this->disk = Storage::disk(config('digitallyhappy.assets.disk'));

        $cachebusting = config('digitallyhappy.assets.cachebusting');
        $this->cachebusting = $cachebusting ? (string) Str::of($cachebusting)->start('?') : '';
    }

    /**
     * Adds the asset to the current loaded assets.
     *
     * @param  string  $asset
     * @return void
     */
    public function markAsLoaded(string $asset): void
    {
        if (! $this->isLoaded($asset)) {
            $this->loaded[] = $asset;
        }
    }

    /**
     * Checks if the asset is already on loaded asset list.
     *
     * @param  string  $asset
     * @return bool
     */
    public function isLoaded(string $asset): bool
    {
        return in_array($asset, $this->loaded);
    }

    /**
     * Returns the current loaded assets on app lifecycle.
     *
     * @return array
     */
    public function loaded(): array
    {
        return $this->loaded;
    }

    /**
     * Outputs a file depending on its type.
     *
     * @param  string  $path
     * @param  array  $attributes
     * @return void
     */
    public function echoFile(string $path, array $attributes = []): void
    {
        if (substr($path, -3) === '.js') {
            $this->echoJs($path, $attributes);
        }

        if (substr($path, -4) === '.css') {
            $this->echoCss($path, $attributes);
        }
    }

    /**
     * Outputs the CSS link tag.
     *
     * @param  string  $path
     * @param  array  $attributes
     * @return void
     */
    private function echoCss(string $path, array $attributes = []): void
    {
        $args = '';
        foreach ($attributes as $key => $value) {
            $args .= " $key".($value === true || empty($value) ? '' : "=\"$value\"");
        }

        echo '<link href="'.asset($path.$this->cachebusting).'"'.$args.' rel="stylesheet" type="text/css" />'.PHP_EOL;
    }

    /**
     * Outputs the JS script tag.
     *
     * @param  string  $path
     * @return void
     */
    private function echoJs(string $path, array $attributes = []): void
    {
        $args = '';
        foreach ($attributes as $key => $value) {
            $args .= " $key".($value === true || empty($value) ? '' : "=\"$value\"");
        }

        echo '<script src="'.asset($path.$this->cachebusting).'"'.$args.'></script>'.PHP_EOL;
    }

    /**
     * Returns the asset proper path and url.
     *
     * @param  string  $asset
     * @return array
     */
    private function getAssetPaths(string $asset): array
    {
        $path = Str::of(config('digitallyhappy.assets.path'))->finish('/')->append(str_replace(['http://', 'https://', '://', '<', '>', ':', '"', '|', '?', "\0", '*', '`', ';', "'", '+'], '', $asset));
        $url = $this->disk->url($path);

        return [
            $path,
            $url,
        ];
    }

    /**
     * Internalize a CDN or local asset.
     *
     * @param  string  $asset
     * @param  mixed  $output
     * @param  array  $attributes
     * @return void
     */
    public function basset(string $asset, mixed $output = true, array $attributes = []): string
    {
        // Validate the asset is an aboslute path or a CDN
        if (! str_starts_with($asset, base_path()) && ! str_starts_with($asset, 'http')) {
            $output && $this->echoFile($asset, $attributes);

            return self::STATUS_INVALID;
        }

        // Override asset in case output is a string
        $path = is_string($output) ? $output : $asset;

        // Remove absolute path
        $path = str_replace(base_path(), '', $path);

        // Get asset paths
        [$path, $url] = $this->getAssetPaths($path);

        if ($this->isLoaded($path)) {
            return self::STATUS_LOADED;
        }

        $this->markAsLoaded($path);

        // Check if asset exists in bassets folder
        if ($this->disk->exists($path)) {
            $output && $this->echoFile($url, $attributes);

            return self::STATUS_IN_CACHE;
        }

        try {
            // Download/copy file content
            $content = file_get_contents($asset);

            // Clean source map
            $content = preg_replace('/sourceMappingURL=/', '', $content);

            $result = $this->disk->put($path, $content);
        } catch (Exception $e) {
            $result = false;
        }

        if ($result) {
            $output && $this->echoFile($url, $attributes);

            return self::STATUS_DOWNLOADED;
        }

        // Fallback to the CDN/path
        $output && $this->echoFile($asset, $attributes);

        return self::STATUS_NO_ACTION;
    }

    /**
     * Internalize a basset code block.
     *
     * @param  string  $asset
     * @param  string  $code
     * @return void
     */
    public function bassetBlock(string $asset, string $code): void
    {
        // Get asset paths
        [$path, $url] = $this->getAssetPaths($asset);

        if ($this->isLoaded($path)) {
            return;
        }

        $this->markAsLoaded($path);

        // Check if asset exists in bassets folder
        if ($this->disk->exists($path)) {
            $this->echoFile($url);

            return;
        }

        // Strip tags
        $cleanCode = preg_replace('/^\s*\<\/?(script|style).*?\/?\s*?\>/ms', '', $code);

        // Clear empty lines
        $cleanCode = preg_replace('/^(?:[\t ]*(?:\r?\n|\r))+/', '', $cleanCode);

        // clean the left padding
        preg_match("/^\s*/", $cleanCode, $matches);
        $cleanCode = preg_replace('/^'.($matches[0] ?? '').'/m', '', $cleanCode);

        // Store the file
        try {
            $result = $this->disk->put($path, $cleanCode);
        } catch (Exception $e) {
            $result = false;
        }

        if ($result) {
            $this->echoFile($url);

            return;
        }

        // Fallback to the code
        echo $code;
    }
}
