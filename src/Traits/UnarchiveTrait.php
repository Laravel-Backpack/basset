<?php

namespace Backpack\Basset\Traits;

use Illuminate\Support\Facades\File;
use PharData;
use ZipArchive;

trait UnarchiveTrait
{
    /**
     * Unarchive files.
     *
     * @param  string  $file  source file
     * @param  string  $output  output destination
     * @return bool result
     */
    public function unarchiveFile(string $file, string $output): bool
    {
        $mimeType = File::mimeType($file);

        switch ($mimeType) {
            // zip
            case 'application/zip':
                return $this->unarchiveZip($file, $output);

                // tar.gz
            case 'application/gzip':
            case 'application/x-gzip':
            case 'application/bzip2':
            case 'application/x-bzip2':
                return $this->unarchiveGz($file, $output);

                // tar
            case 'application/x-tar':
                return $this->unarchiveTar($file, $output);
        }

        return false;
    }

    /**
     * Unarchive zip files.
     *
     * @param  string  $file  source file
     * @param  string  $output  output destination
     * @return bool result
     */
    private function unarchiveZip(string $file, string $output): bool
    {
        $zip = new ZipArchive();
        $zip->open($file);
        $result = $zip->extractTo($output);
        $zip->close();

        return $result;
    }

    /**
     * Unarchive gz files.
     *
     * @param  string  $file  source file
     * @param  string  $output  output destination
     * @return bool result
     */
    private function unarchiveGz(string $file, string $output): bool
    {
        $phar = new PharData($file);
        $tar = $phar->decompress()->getPath();

        $result = $this->unarchiveTar($tar, $output);
        unlink($tar);

        return $result;
    }

    /**
     * Unarchive tar files.
     *
     * @param  string  $file  source file
     * @param  string  $output  output destination
     * @return bool result
     */
    private function unarchiveTar(string $file, string $output): bool
    {
        $phar = new PharData($file);

        return $phar->extractTo($output);
    }

    /**
     * Returns a temporary file path.
     *
     * @return string
     */
    public function getTemporaryFilePath(): string
    {
        return tempnam(sys_get_temp_dir(), '');
    }

    /**
     * Returns a temporary directory path.
     *
     * @return string
     */
    public function getTemporaryDirectoryPath(): string
    {
        $dir = storage_path('app/tmp/'.mt_rand().'/');
        File::ensureDirectoryExists($dir);

        return $dir;
    }
}
