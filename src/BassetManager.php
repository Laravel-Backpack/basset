<?php

namespace Backpack\Basset;

use Backpack\Basset\Enums\StatusEnum;
use Backpack\Basset\Helpers\CacheMap;
use Backpack\Basset\Helpers\LoadingTime;
use Backpack\Basset\Helpers\Unarchiver;
use Illuminate\Filesystem\FilesystemAdapter;
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

    private FilesystemAdapter $disk;
    private array $loaded;
    private string $basePath;
    private string $cachebusting;
    private string|null $nonce;
    private bool $dev = false;
    private bool $useRelativePaths = true;

    public CacheMap $cacheMap;
    public LoadingTime $loader;
    public Unarchiver $unarchiver;

    public function __construct()
    {
        $this->loaded = [];

        /** @var FilesystemAdapter */
        $disk = Storage::disk(config('backpack.basset.disk'));

        $this->disk = $disk;
        $this->cachebusting = '?'.substr(md5(base_path('composer.lock')), 0, 12);
        $this->basePath = (string) Str::of(config('backpack.basset.path'))->finish('/');
        $this->dev = config('backpack.basset.dev_mode', false);
        $this->nonce = config('backpack.basset.nonce', null);
        $this->useRelativePaths = config('backpack.basset.relative_paths', true);

        $this->cacheMap = new CacheMap($this->disk, $this->basePath);
        $this->loader = new LoadingTime();
        $this->unarchiver = new Unarchiver();

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
        $href = $this->assetPath($path);
        $args = $this->prepareAttributes($attributes);

        echo '<link href="'.$href.'"'.$args.' rel="stylesheet" type="text/css" />'.PHP_EOL;
    }

    /**
     * Outputs the JS script tag.
     *
     * @param  string  $path
     * @return void
     */
    public function echoJs(string $path, array $attributes = []): void
    {
        $src = $this->assetPath($path);
        $args = $this->prepareAttributes($attributes);

        echo '<script src="'.$src.'"'.$args.'></script>'.PHP_EOL;
    }

    /**
     * Generates the asset path.
     *
     * @param  string  $path
     * @return string
     */
    public function assetPath(string $path): string
    {
        $asset = Str::of(asset($path.$this->cachebusting));

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

    /**
     * Returns the asset path.
     *
     * @param  string  $asset
     * @return string
     */
    public function getPath(string $asset): string
    {
        return Str::of($this->basePath)
            ->append(str_replace([base_path().'/', base_path(), 'http://', 'https://', '://', '<', '>', ':', '"', '|', "\0", '*', '`', ';', "'", '+'], '', $asset))
            ->before('?')
            ->replace('/\\', '/');
    }

    /**
     * Gets the name of the file with the hash corresponding to the code block.
     *
     * @param  string  $asset
     * @param  string  $content
     * @return string
     */
    public function getPathHashed(string $asset, string $content): string
    {
        $path = $this->getPath($asset);

        // get the hash for the content
        $hash = substr(md5($content), 0, 8);

        return preg_replace('/\.(css|js)$/i', "-{$hash}.$1", $path);
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
     * @param  bool | string  $output
     * @param  array  $attributes
     * @return StatusEnum
     */
    public function basset(string $asset, bool|string $output = true, array $attributes = []): StatusEnum
    {
        $this->loader->start();

        // Get asset path
        $path = $this->getPath(is_string($output) ? $output : $asset);

        if ($this->isLoaded($path)) {
            return $this->loader->finish(StatusEnum::LOADED);
        }

        $this->markAsLoaded($path);

        // Retrieve from map
        $mapped = $this->cacheMap->getAsset($asset);
        if ($mapped && ! $this->dev) {
            $output && $this->echoFile($mapped, $attributes);

            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

        // Validate the asset is an absolute path or a CDN
        if (! str_starts_with($asset, base_path()) && ! Str::isUrl($asset)) {
            // may be an internalized asset (folder or zip)
            if ($this->disk->exists($path)) {
                $asset = $this->disk->url($path);
                $output && $this->echoFile($asset, $attributes);

                return $this->loader->finish(StatusEnum::IN_CACHE);
            }

            // public file (default fallback)
            $output && $this->echoFile($asset, $attributes);

            return $this->loader->finish(StatusEnum::INVALID);
        }

        // Get asset url
        $url = $this->disk->url($path);

        // Check if asset exists in basset folder
        // (ignores cache if in dev mode)
        if ($this->disk->exists($path) && ! $this->dev) {
            $output && $this->echoFile($url, $attributes);
            $this->cacheMap->addAsset($asset, $url);

            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

        // Download/copy file
        if (Str::isUrl($asset)) {
            // when in dev mode, cdn should be rendered
            if ($this->dev) {
                $output && $this->echoFile($asset, $attributes);

                return $this->loader->finish(StatusEnum::DISABLED);
            }

            $content = $this->fetchContent($asset);
        } else {
            // clean local asset
            $asset = Str::before($asset, '?');

            if (! File::exists($asset)) {
                return $this->loader->finish(StatusEnum::INVALID);
            }
            $content = File::get($asset);
        }

        // Clean source map
        $content = preg_replace('/sourceMappingURL=/', '', $content);

        $result = $this->disk->put($path, $content, 'public');

        if ($result) {
            $output && $this->echoFile($url, $attributes);
            $this->cacheMap->addAsset($asset, $url);

            return $this->loader->finish(StatusEnum::INTERNALIZED);
        }

        // Fallback to the CDN/path
        $output && $this->echoFile($asset, $attributes);

        return $this->loader->finish(StatusEnum::INVALID);
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
        $this->loader->start();

        // Get asset path and url
        $path = $this->getPathHashed($asset, $code);

        if ($this->isLoaded($path)) {
            return $this->loader->finish(StatusEnum::LOADED);
        }

        $this->markAsLoaded($path);

        // fallback to code on dev mode
        if ($this->dev) {
            echo $code;

            return $this->loader->finish(StatusEnum::DISABLED);
        }

        // Retrieve from map
        $mapped = $this->cacheMap->getAsset($asset);
        if ($mapped) {
            $output && $this->echoFile($mapped);

            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

        // Get asset url
        $url = $this->disk->url($path);

        // Check if asset exists in basset folder
        if ($this->disk->exists($path)) {
            $output && $this->echoFile($url);
            $this->cacheMap->addAsset($asset, $url);

            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

        // Strip tags
        $cleanCode = preg_replace('/^\s*\<\/?(script|style).*?\/?\s*?\>/ms', '', $code);

        // Clear empty lines
        $cleanCode = preg_replace('/^(?:[\t ]*(?:\r?\n|\r))+/', '', $cleanCode);

        // clean the left padding
        preg_match("/^\s*/", $cleanCode, $matches);
        $cleanCode = preg_replace('/^'.($matches[0] ?? '').'/m', '', $cleanCode);

        // Store the file
        $result = $this->disk->put($path, $cleanCode, 'public');

        // Delete old hashed files
        $dir = Str::beforeLast($path, '/');
        $pattern = '\/'.Str::afterLast(preg_replace('/\w{8}\.(css|js)$/i', '\w{8}.$1', $path), '/');

        collect($this->disk->files($dir))
            ->filter(fn ($file) => $file !== $path && preg_match("/$pattern/", $file))
            ->each(fn ($file) => $this->disk->delete($file));

        // Output result
        if ($result) {
            $output && $this->echoFile($url);
            $this->cacheMap->addAsset($asset, $url);

            return $this->loader->finish(StatusEnum::INTERNALIZED);
        }

        // Fallback to the code
        echo $code;

        return $this->loader->finish(StatusEnum::INVALID);
    }

    /**
     * Internalize an Archive.
     *
     * @param  string  $asset
     * @param  string  $output
     * @return StatusEnum
     */
    public function bassetArchive(string $asset, string $output): StatusEnum
    {
        $this->loader->start();

        // get local output path
        $path = $this->getPath($output);
        $output = $this->disk->path($path);

        // Check if asset is loaded
        if ($this->isLoaded($path)) {
            return $this->loader->finish(StatusEnum::LOADED);
        }

        $this->markAsLoaded($path);

        // Retrieve from map
        if ($this->cacheMap->getAsset($asset)) {
            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

        // local zip file
        if (File::isFile($asset)) {
            $file = $asset;
        }

        // online zip
        if (Str::isUrl($asset)) {
            // check if directory exists
            if ($this->disk->exists($path)) {
                return $this->loader->finish(StatusEnum::IN_CACHE);
            }

            // temporary file
            $file = $this->unarchiver->getTemporaryFilePath();

            // download file to temporary location
            $content = $this->fetchContent($asset);
            File::put($file, $content);
        }

        // local zip file
        if (File::isFile($asset)) {
            // check if directory exists
            if ($this->disk->exists($path) && ! $this->dev) {
                return $this->loader->finish(StatusEnum::IN_CACHE);
            }

            $file = $asset;
        }

        if (! isset($file)) {
            return $this->loader->finish(StatusEnum::INVALID);
        }

        $tempDir = $this->unarchiver->getTemporaryDirectoryPath();
        $this->unarchiver->unarchiveFile($file, $tempDir);

        // internalize all files in the folder
        foreach (File::allFiles($tempDir) as $file) {
            $this->disk->put("$path/{$file->getRelativePathName()}", File::get($file), 'public');
        }

        File::delete($tempDir);
        $this->cacheMap->addAsset($asset);

        return $this->loader->finish(StatusEnum::INTERNALIZED);
    }

    /**
     * Internalize a Directory.
     *
     * @param  string  $asset
     * @param  string  $output
     * @return StatusEnum
     */
    public function bassetDirectory(string $asset, string $output): StatusEnum
    {
        $this->loader->start();

        // get local output path
        $path = $this->getPath($output);

        // Check if asset is loaded
        if ($this->isLoaded($path)) {
            return $this->loader->finish(StatusEnum::LOADED);
        }

        $this->markAsLoaded($path);

        // Retrieve from map
        if ($this->cacheMap->getAsset($asset)) {
            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

        // check if directory exists
        // if dev mode is active it should ignore the cache
        if ($this->disk->exists($path) && ! $this->dev) {
            $this->cacheMap->addAsset($asset);

            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

        // check if folder exists in filesystem
        if (! File::exists($asset)) {
            return $this->loader->finish(StatusEnum::INVALID);
        }

        // internalize all files in the folder
        foreach (File::allFiles($asset) as $file) {
            $this->disk->put("$path/{$file->getRelativePathName()}", File::get($file), 'public');
        }

        $this->cacheMap->addAsset($asset);

        return $this->loader->finish(StatusEnum::INTERNALIZED);
    }

    /**
     * Fetch the content body of an url.
     */
    public function fetchContent(string $url): string
    {
        return Http::withOptions(['verify' => config('backpack.basset.verify_ssl_certificate', true)])
            ->get($url)
            ->body();
    }
}
