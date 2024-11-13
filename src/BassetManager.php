<?php

namespace Backpack\Basset;

use Backpack\Basset\Enums\StatusEnum;
use Backpack\Basset\Events\BassetCachedEvent;
use Backpack\Basset\Helpers\CacheEntry;
use Backpack\Basset\Helpers\CacheMap;
use Backpack\Basset\Helpers\FileOutput;
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

    private bool $dev;

    private bool $forceUrlCache;

    private array $namedAssets = [];

    public CacheMap $cacheMap;

    public LoadingTime $loader;

    public Unarchiver $unarchiver;

    public FileOutput $output;

    public function __construct()
    {
        $this->loaded = [];

        /** @var FilesystemAdapter */
        $disk = Storage::disk(config('backpack.basset.disk'));
        $this->disk = $disk;
        $this->basePath = (string) Str::of(config('backpack.basset.path'))->finish('/');
        $this->dev = config('backpack.basset.dev_mode', false);
        $this->forceUrlCache = config('backpack.basset.force_url_cache', false);

        $this->cacheMap = new CacheMap($this->disk, $this->basePath);
        $this->loader = new LoadingTime();
        $this->unarchiver = new Unarchiver();
        $this->output = new FileOutput();

        // initialize static view path methods
        $this->initViewPaths();
    }

    public function cacheMap(): CacheMap
    {
        return $this->cacheMap;
    }

    /**
     * Adds the basset to the current loaded basset list.
     *
     * @return void
     */
    public function markAsLoaded(CacheEntry|string $asset): void
    {
        if(is_string($asset)) {
            $asset = isset($this->namedAssets[$asset]) ? $this->namedAssets[$asset] : [$asset => ['source' => $asset]];
            $asset = (new CacheEntry($this->basePath))->assetName($asset)->assetPath($asset['source']);
        }

        if (! $this->isLoaded($asset)) {
            $this->loaded[$asset->getAssetName()] = $asset->toArray();
        }
    }

    public function map(string $asset, string $source, array $attributes = []): void
    {
        $this->namedAssets[$asset] = [
            'source'     => $source,
            'attributes' => $attributes,
        ];
    }

    public function getNamedAssets(): array
    {
        return $this->namedAssets;
    }

    /**
     * Checks if the asset is already on loaded asset list.
     *
     * @return bool
     */
    public function isLoaded(CacheEntry $asset): bool
    {
        return in_array($asset->getAssetName(), array_keys($this->loaded));
    }

    /**
     * Returns the current loaded basset list on app lifecycle.
     *
     * @return array
     */
    public function loaded(): array
    {
        return array_keys($this->loaded);
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
        $hash = hash('xxh32', $content);

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
        if(isset($this->namedAssets[$asset])) {
            if($this->dev) {
                return $this->namedAssets[$asset]['source'];
            }
            return $this->disk->url($this->getPath($this->namedAssets[$asset]['source']));
        }
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
        $cacheEntry = new CacheEntry($this->basePath);

        if (isset($this->namedAssets[$asset])) {
            $cacheEntry->assetName($asset)
                        ->assetPath($this->namedAssets[$asset]['source'])
                        ->attributes(array_merge($attributes, $this->namedAssets[$asset]['attributes']));

            return $this->loadAsset($cacheEntry, $output);
        }

        $cacheEntry->assetName($asset)
                    ->assetPath($asset)
                    ->attributes($attributes);

        return $this->loadAsset($cacheEntry, $output);
    }

    public function loadAsset(CacheEntry $asset, $output)
    {
        if ($this->isLoaded($asset)) {
            return $this->loader->finish(StatusEnum::LOADED);
        }

        $this->markAsLoaded($asset);

        // Retrieve from map
        /** var CacheEntry $mapped */
        $mapped = $this->cacheMap->getAsset($asset);

        if ($mapped && (! $this->dev || $this->forceUrlCache)) {
            $output && $this->output->write($mapped);

            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

         // Validate the asset is an absolute path or a CDN
        if (! str_starts_with($asset->getAssetPath(), base_path()) && ! Str::isUrl($asset->getAssetPath())) {
            // may be an internalized asset (folder or zip)
            if ($asset->existsOnDisk($this->disk)) {
                $output && $this->output->write($asset);

                return $this->loader->finish(StatusEnum::IN_CACHE);
            }

            // public file (default fallback)
            $output && $this->output->write($asset);

            return $this->loader->finish(StatusEnum::INVALID);
        }

        // Check if asset exists in basset folder
        // (ignores cache if in dev mode)
        if ($asset->existsOnDisk($this->disk) && ! $this->dev) {
            $output && $this->output->write($asset);
            $this->cacheMap->addAsset($asset);

            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

        // Download/copy file
        if (Str::isUrl($asset->getAssetPath())) {
            // when in dev mode, cdn should be rendered
            if ($this->dev && ! $this->forceUrlCache) {
                $output && $this->output->write($asset, $this->dev);

                return $this->loader->finish(StatusEnum::DISABLED);
            }

            $content = $this->fetchContent($asset->getAssetPath());
        } else {
            if (! $asset->existsOnLocalPath()) {
                return $this->loader->finish(StatusEnum::INVALID);
            }
            $content = File::get($asset->getAssetPath());
        }

        $result = $this->disk->put($asset->getAssetDiskPath(), $content, 'public');

        if ($result) {
            $output && $this->output->write($asset);
            $this->cacheMap->addAsset($asset);

            BassetCachedEvent::dispatch($asset->getAssetPath());

            return $this->loader->finish(StatusEnum::INTERNALIZED);
        }
      
        // Fallback to the CDN/path
        $output && $this->output->write($asset);

        return $this->loader->finish(StatusEnum::INVALID);
    }

    /**
     * Internalize a basset code block.
     *
     * @param  string  $asset
     * @param  string  $code
     * @return StatusEnum
     */
    public function bassetBlock(string $asset, string $code, bool $output = true, bool $cache = true): StatusEnum
    {
        $this->loader->start();
        $asset = (new CacheEntry($this->basePath))->assetName($asset)->assetPath($asset);

        // when cache is set to false we will just mark the asset as loaded to avoid
        // loading the same asset twice and return the raw code to the browser.
        if ($cache === false) {
            if ($this->isloaded($asset)) {
                return $this->loader->finish(StatusEnum::LOADED);
            }
            $this->markAsLoaded($asset);

            echo $code;

            return $this->loader->finish(StatusEnum::LOADED);
        }

        // Get asset path and url
        $path = $this->getPathHashed($asset->getAssetPath(), $code);

        if ($this->isLoaded($asset)) {
            return $this->loader->finish(StatusEnum::LOADED);
        }

        $this->markAsLoaded($asset);

        // fallback to code on dev mode
        if ($this->dev) {
            echo $code;

            return $this->loader->finish(StatusEnum::DISABLED);
        }

        // Retrieve from map
        $mapped = $this->cacheMap->getAsset($asset);
        if ($mapped) {
            $output && $this->output->write($mapped);

            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

        // Check if asset exists in basset folder
        if ($asset->existsOnDisk($this->disk)) {
            $output && $this->output->write($asset);
            $this->cacheMap->addAsset($asset);

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
        $result = $this->disk->put($asset->getAssetDiskPath(), $cleanCode, 'public');

        // Delete old hashed files
        $dir = Str::beforeLast($asset->getAssetDiskPath(), '/');
        $pattern = '\/'.Str::afterLast(preg_replace('/\w{8}\.(css|js)$/i', '\w{8}.$1', $path), '/');

        collect($this->disk->files($dir))
            ->filter(fn ($file) => $file !== $path && preg_match("/$pattern/", $file))
            ->each(fn ($file) => $this->disk->delete($file));

        // Output result
        if ($result) {
            $output && $this->output->write($asset);
            $this->cacheMap->addAsset($asset);

            BassetCachedEvent::dispatch($asset->getAssetPath());

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
        $cacheEntry = (new CacheEntry($this->basePath))->assetName($asset)->assetPath($asset);

        // get local output path
        $path = $this->getPath($output);
        $output = $this->disk->path($path);

        // Check if asset is loaded
        if ($this->isLoaded($cacheEntry)) {
            return $this->loader->finish(StatusEnum::LOADED);
        }

        $this->markAsLoaded($cacheEntry);

        // Retrieve from map
        if ($this->cacheMap->getAsset($cacheEntry)) {
            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

        // local zip file
        if (File::isFile($cacheEntry->getAssetPath())) {
            $file = $cacheEntry->getAssetPath();
        }

        // online zip
        if (Str::isUrl($cacheEntry->getAssetPath())) {
            // check if directory exists
            if ($this->disk->exists($cacheEntry->getAssetPath())) {
                return $this->loader->finish(StatusEnum::IN_CACHE);
            }

            // temporary file
            $file = $this->unarchiver->getTemporaryFilePath();

            // download file to temporary location
            $content = $this->fetchContent($cacheEntry->getAssetPath());
            File::put($file, $content);
        }

        // local zip file
        if (File::isFile($cacheEntry->getAssetPath())) {
            // check if directory exists
            if ($cacheEntry->existsOnDisk($this->disk) && ! $this->dev) {
                return $this->loader->finish(StatusEnum::IN_CACHE);
            }

            $file = $cacheEntry->getAssetPath();
        }

        if (! isset($file)) {
            return $this->loader->finish(StatusEnum::INVALID);
        }

        $tempDir = $this->unarchiver->getTemporaryDirectoryPath();
        $fileName = basename($file);

        // first copy the file to the temporary folder, that way we are sure that the folder is writable
        File::copy($file, $tempDir.$fileName);
        $this->unarchiver->unarchiveFile($tempDir.$fileName, $tempDir);

        // internalize all files in the folder except the zip file itself
        foreach (File::allFiles($tempDir) as $file) {
            if ($file->getRelativePathName() !== $fileName) {
                $this->disk->put("$path/{$file->getRelativePathName()}", File::get($file), 'public');
            }
        }
        // delete the whole temporary folder
        File::deleteDirectory($tempDir);

        $this->cacheMap->addAsset($cacheEntry);

        BassetCachedEvent::dispatch($cacheEntry->getAssetPath());

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

        $cacheEntry = (new CacheEntry($this->disk, $this->basePath))->assetName($asset)->assetPath($output);

        // Check if asset is loaded
        if ($this->isLoaded($cacheEntry)) {
            return $this->loader->finish(StatusEnum::LOADED);
        }

        $this->markAsLoaded($cacheEntry);

        // Retrieve from map
        if ($this->cacheMap->getAsset($cacheEntry)) {
            return $this->loader->finish(StatusEnum::IN_CACHE);
        }

        // check if directory exists
        // if dev mode is active it should ignore the cache
        if ($this->disk->exists($path) && ! $this->dev) {
            $this->cacheMap->addAsset($cacheEntry);

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

        $this->cacheMap->addAsset($cacheEntry);

        BassetCachedEvent::dispatch($asset);

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
