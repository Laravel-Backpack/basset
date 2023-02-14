<?php

namespace DigitallyHappy\Assets;

use Exception;
use Illuminate\Support\Str;

class AssetManager
{
    const TYPE_STYLE = 'style';
    const TYPE_SCRIPT = 'script';

    const STATUS_DISABLED = 'Cache CDN is disabled in the configuration.';
    const STATUS_LOCAL = 'Asset is not in a CDN.';
    const STATUS_IN_CACHE = 'Asset was already in cache.';
    const STATUS_DOWNLOADED = 'Asset downloaded.';
    const STATUS_NO_ACTION = 'Asset was not downloaded, falling back to CDN.';

    private $loaded;

    public function __construct()
    {
        $this->loaded = [];
    }

    /**
     * Outputs a file depending on its type.
     *
     * @param  string  $path
     * @param  array   $attributes
     * @param  string  $type
     * @return void
     */
    public function echoFile(string $path, array $attributes = [], string $type = null): void
    {
        if ($type === self::TYPE_SCRIPT || substr($path, -3) === '.js') {
            $this->echoJs($path, $attributes);
        }

        if ($type === self::TYPE_STYLE || substr($path, -4) === '.css') {
            $this->echoCss($path, $attributes);
        }
    }

    /**
     * Outputs the CSS link tag.
     *
     * @param  string  $path
     * @param  array   $attributes
     * @param  string  $type
     * @return void
     */
    public function echoCss(string $path, array $attributes = [], $suffix = PHP_EOL): void
    {
        if ($this->isLoaded($path)) {
            return;
        }

        $this->markAsLoaded($path);

        $args = '';
        foreach ($attributes as $key => $value) {
            $args .= " $key".($value === true || empty($value) ? '' : "=\"$value\"");
        }

        echo '<link href="'.asset($path).'"'.$args.' rel="stylesheet" type="text/css" />'.$suffix;
    }

    /**
     * Outputs the JS script tag.
     *
     * @param  string  $path
     * @return void
     */
    public function echoJs(string $path, array $attributes = [], $suffix = PHP_EOL): void
    {
        if ($this->isLoaded($path)) {
            return;
        }

        $this->markAsLoaded($path);

        $args = '';
        foreach ($attributes as $key => $value) {
            $args .= " $key".($value === true || empty($value) ? '' : "=\"$value\"");
        }

        echo '<script src="'.asset($path).'"'.$args.'></script>'.$suffix;
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
     * Localize a CDN asset.
     *
     * @param  string  $asset
     * @param  mixed   $output
     * @param  array   $attributes
     * @param  string  $type
     * @return void
     */
    public function basset(string $asset, mixed $output = true, array $attributes = [], string $type = null): string
    {
        // Valiate user configuration
        if (! config('digitallyhappy.assets.cache_cdns')) {
            $output && $this->echoFile($asset, $attributes, $type);

            return self::STATUS_DISABLED;
        }

        // Make sure asset() is removed
        $asset = str_replace(asset(''), '', $asset);

        // Validate the asset comes from a CDN
        if (substr($asset, 0, 4) !== 'http') {
            $output && $this->echoFile($asset, $attributes, $type);

            return self::STATUS_LOCAL;
        }

        // Override asset in case output is a string
        if (is_string($output)) {
            $asset = $output;
        }

        $assetSlug = str_replace(['http://', 'https://', '://', '<', '>', ':', '"', '|', '?', "\0", '*', '`', ';', "'", '+'], '', $asset);

        $localizedFilePath = Str::of(config('digitallyhappy.assets.cache_path'))->trim('\\/')->append("/$assetSlug");
        $localizedUrl = Str::of(config('digitallyhappy.assets.cache_public_path'))->trim('\\/')->append("/$assetSlug");
        $localizedPath = $localizedFilePath->beforeLast('/');

        // Check if asset exists in bassets folder
        if (is_file($localizedFilePath)) {
            $output && $this->echoFile($localizedUrl, $attributes, $type);

            return self::STATUS_IN_CACHE;
        }

        // Create the directory
        if (! is_dir($localizedPath)) {
            mkdir($localizedPath, recursive:true);
        }

        try {
            // Download file
            $content = file_get_contents($asset);

            // Clean source map
            $content = preg_replace('/sourceMappingURL=/', '', $content);

            $result = file_put_contents($localizedFilePath, $content);
        } catch (Exception $e) {
            $result = false;
        }

        if ($result) {
            $output && $this->echoFile($localizedUrl, $attributes, $type);

            return self::STATUS_DOWNLOADED;
        }

        // Fallback to the CDN
        $output && $this->echoFile($asset, $attributes, $type);

        return self::STATUS_NO_ACTION;
    }
}
