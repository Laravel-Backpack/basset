<?php

namespace DigitallyHappy\Assets;

class AssetManager
{
    public $loaded_assets;

    public function __construct()
    {
        $this->loaded_assets = [];
    }

    public function echoCss($path)
    {
        if ($this->isAssetLoaded($path)) {
            return;
        }

        $this->markAssetAsLoaded($path);

        echo '<link href="'.asset($path).'" rel="stylesheet" type="text/css" />';
    }

    public function echoJs($path)
    {
        if ($this->isAssetLoaded($path)) {
            return;
        }

        $this->markAssetAsLoaded($path);

        echo '<script src="'.asset($path).'"></script>';
    }

    /**
     * Adds the asset to the current loaded assets.
     *
     * @param  string  $asset
     * @return void
     */
    public function markAssetAsLoaded($asset)
    {
        if (! $this->isAssetLoaded($asset)) {
            $this->loaded_assets[] = $asset;
        }
    }

    /**
     * Checks if the asset is already on loaded asset list.
     *
     * @param  string  $asset
     * @return bool
     */
    public function isAssetLoaded($asset)
    {
        if (in_array($asset, $this->loaded_assets)) {
            return true;
        }

        return false;
    }

    /**
     * Returns the current loaded assets on app lifecycle.
     *
     * @return array
     */
    public function loadedAssets()
    {
        return $this->loaded_assets;
    }
}
