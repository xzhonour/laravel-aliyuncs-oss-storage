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
}
