<?php

namespace Backpack\Basset;

use Backpack\Basset\Enums\StatusEnum;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Basset Manager.
 */
class BassetManager
{
    use Traits\ViewPathsTrait;
    use Traits\UnarchiveTrait;

    private $loaded;
    private $disk;
    private $basePath;
    private $cachebusting;

    public function __construct()
    {
        $this->loaded = [];
        $this->disk = Storage::disk(config('backpack.basset.disk'));

        $cachebusting = config('backpack.basset.cachebusting');
        $this->cachebusting = $cachebusting ? (string) Str::of($cachebusting)->start('?') : '';
        $this->basePath = (string) Str::of(config('backpack.basset.path'))->finish('/');

        // initialize static view path methods
        $this->initViewPaths();
    }

    /**
     * Adds the basset to the current loaded basset list.
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
     * Returns the current loaded basset list on app lifecycle.
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
    public function echoCss(string $path, array $attributes = []): void
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
    public function echoJs(string $path, array $attributes = []): void
    {
        $args = '';
        foreach ($attributes as $key => $value) {
            $args .= " $key".($value === true || empty($value) ? '' : "=\"$value\"");
        }

        echo '<script src="'.asset($path.$this->cachebusting).'"'.$args.'></script>'.PHP_EOL;
    }

    /**
     * Returns the asset path.
     *
     * @param  string  $asset
     * @return string
     */
    public function getPath(string $asset): string
    {
        return Str::of($this->basePath)
            ->append(str_replace([base_path(), 'http://', 'https://', '://', '<', '>', ':', '"', '|', '?', "\0", '*', '`', ';', "'", '+'], '', $asset))
            ->replace('/\\', '/');
    }

    /**
     * Returns the asset url.
     *
     * @param  string  $asset
     * @return string
     */
    public function getUrl(string $asset): string
    {
        return $this->disk->url($this->getPath($asset));
    }

    /**
     * Internalize a CDN or local asset.
     *
     * @param  string  $asset
     * @param  mixed  $output
     * @param  array  $attributes
     * @return StatusEnum
     */
    public function basset(string $asset, bool | string $output = true, array $attributes = []): StatusEnum
    {
        // Get asset path
        $path = $this->getPath(is_string($output) ? $output : $asset);

        if ($this->isLoaded($path)) {
            return StatusEnum::LOADED;
        }

        $this->markAsLoaded($path);

        // Validate the asset is an absolute path or a CDN
        if (! str_starts_with($asset, base_path()) && ! str_starts_with($asset, 'http') && ! str_starts_with($asset, '://')) {

            // may be an internalized asset (folder or zip)
            if ($this->disk->exists($path)) {
                $asset = $this->disk->url($path);
                $output && $this->echoFile($asset, $attributes);

                return StatusEnum::IN_CACHE;
            }

            // public file (default fallback)
            $output && $this->echoFile($asset, $attributes);

            return StatusEnum::INVALID;
        }

        // Get asset url
        $url = $this->disk->url($path);

        // Check if asset exists in basset folder
        if ($this->disk->exists($path)) {
            $output && $this->echoFile($url, $attributes);

            return StatusEnum::IN_CACHE;
        }

        // Download/copy file
        if (str_starts_with($asset, 'http') || str_starts_with($asset, '://')) {
            $content = Http::get($asset)->getBody();
        } else {
            $content = File::get($asset);
        }

        // Clean source map
        $content = preg_replace('/sourceMappingURL=/', '', $content);

        $result = $this->disk->put($path, $content);

        if ($result) {
            $output && $this->echoFile($url, $attributes);

            return StatusEnum::INTERNALIZED;
        }

        // Fallback to the CDN/path
        $output && $this->echoFile($asset, $attributes);

        return StatusEnum::INVALID;
    }

    /**
     * Internalize a basset code block.
     *
     * @param  string  $asset
     * @param  string  $code
     * @return StatusEnum
     */
    public function bassetBlock(string $asset, string $code, bool $output = true): StatusEnum
    {
        // Get asset path and url
        $path = $this->getPath($asset);
        $url = $this->disk->url($path);

        if ($this->isLoaded($path)) {
            return StatusEnum::LOADED;
        }

        $this->markAsLoaded($path);

        // Check if asset exists in basset folder
        if ($this->disk->exists($path)) {
            $output && $this->echoFile($url);

            return StatusEnum::IN_CACHE;
        }

        // Strip tags
        $cleanCode = preg_replace('/^\s*\<\/?(script|style).*?\/?\s*?\>/ms', '', $code);

        // Clear empty lines
        $cleanCode = preg_replace('/^(?:[\t ]*(?:\r?\n|\r))+/', '', $cleanCode);

        // clean the left padding
        preg_match("/^\s*/", $cleanCode, $matches);
        $cleanCode = preg_replace('/^'.($matches[0] ?? '').'/m', '', $cleanCode);

        // Store the file
        $result = $this->disk->put($path, $cleanCode);

        if ($result) {
            $output && $this->echoFile($url);

            return StatusEnum::INTERNALIZED;
        }

        // Fallback to the code
        echo $code;

        return StatusEnum::INVALID;
    }

    /**
     * Internalize an Archive.
     *
     * @param  string  $asset
     * @param  string|null  $output
     * @return StatusEnum
     */
    public function bassetArchive(string $asset, string $output): StatusEnum
    {
        // get local output path
        $path = $this->getPath($output);
        $output = $this->disk->path($path);

        // Check if asset is loaded
        if ($this->isLoaded($path)) {
            return StatusEnum::LOADED;
        }

        $this->markAsLoaded($path);

        // check if directory exists
        if ($this->disk->exists($path)) {
            return StatusEnum::IN_CACHE;
        }

        // local zip file
        if (File::isFile($asset)) {
            $file = $asset;
        }

        // online zip
        if (str_starts_with($asset, 'http') || str_starts_with($asset, '://')) {
            // temporary file
            $file = $this->getTemporaryFilePath();

            // download file to temporary location
            $content = Http::get($asset)->getBody();
            File::put($file, $content);
        }

        if (! isset($file)) {
            return StatusEnum::INVALID;
        }

        $tempDir = $this->getTemporaryDirectoryPath();
        $this->unarchiveFile($file, $tempDir);

        // internalize all files in the folder
        foreach (File::allFiles($tempDir) as $file) {
            $this->disk->put("$path/{$file->getRelativePathName()}", File::get($file));
        }

        File::delete($tempDir);

        return StatusEnum::INTERNALIZED;
    }

    /**
     * Internalize a Directory.
     *
     * @param  string  $asset
     * @param  string|null  $output
     * @return StatusEnum
     */
    public function bassetDirectory(string $asset, string $output): StatusEnum
    {
        // get local output path
        $path = $this->getPath($output);

        // Check if asset is loaded
        if ($this->isLoaded($path)) {
            return StatusEnum::LOADED;
        }

        $this->markAsLoaded($path);

        // check if directory exists
        if ($this->disk->exists($path)) {
            return StatusEnum::IN_CACHE;
        }

        // check if folder exists in filesystem
        if (! File::exists($asset)) {
            return StatusEnum::INVALID;
        }

        // internalize all files in the folder
        foreach (File::allFiles($asset) as $file) {
            $this->disk->put("$path/{$file->getRelativePathName()}", File::get($file));
        }

        return StatusEnum::INTERNALIZED;
    }
}
