<?php

namespace XzHonour\AliOSS;

use Illuminate\Http\UploadedFile;
use League\Flysystem\Config;
use League\Flysystem\Filesystem as FilesystemBase;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use XzHonour\AliOSS\League\Flysystem\Plugin\PluggableTrait;

class Filesystem extends FilesystemBase
{
    use PluggableTrait;

    /**
     * @var FilesystemAdapter
     */
    protected FilesystemAdapter $adapter;

    /**
     * @var Config
     */
    protected Config $config;

    public function __construct(
        FilesystemAdapter $adapter,
        array $config = [],
        PathNormalizer $pathNormalizer = null
    ) {
        parent::__construct($adapter,$config, $pathNormalizer);
        $this->adapter = $adapter;
        $this->config = new Config($config);
    }


    /**
     * Get the Adapter.
     *
     * @return FilesystemAdapter adapter
     */
    public function getAdapter(): FilesystemAdapter
    {
        return $this->adapter;
    }


    /**
     * Get the Adapter.
     *
     * @return Config adapter
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

//    /**
//     * @param $path
//     * @return string
//     */
//    public function url(string $path): string
//    {
//        return $this->adapter->getUrl($path);
//    }
//
//    /**
//     * @param string $path
//     * @return bool
//     * @throws \League\Flysystem\FilesystemException
//     */
//    public function exists(string $path): bool
//    {
//        return $this->adapter->fileExists($path);
//    }
//
//    /**
//     * @param $path
//     * @param UploadedFile $file
//     * @param $filename
//     * @return false|string
//     */
//    public function putFileAs($path, UploadedFile $file, $filename): bool|string
//    {
//        $fullPath = $path . '/' . $filename;
//
//        $stream = fopen(is_string($file) ? $file : $file->getRealPath(), 'r');
//
//        $result = true;
//
//        try {
//            $this->adapter->writeStream($fullPath, $stream, $this->config);
//        }catch (\Throwable $t){
//            $result =  false;
//        }
//
//        if (is_resource($stream)) {
//            fclose($stream);
//        }
//
//        return $result ? $fullPath : false;
//    }
}
