<?php

namespace XzHonour\AliOSS\Plugins;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use XzHonour\AliOSS\League\Flysystem\Plugin\AbstractPlugin as BaseAbstractPlugin;

abstract class AbstractPlugin extends BaseAbstractPlugin
{
    /**
     * @var FilesystemOperator
     */
    protected FilesystemOperator $filesystem;

    /**
     * @var \XzHonour\AliOSS\Adapter
     */
    protected \XzHonour\AliOSS\Adapter $adapter;

    /**
     * Set the Filesystem object.
     */
    public function setFilesystem(FilesystemOperator $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->adapter = $filesystem->getAdapter();
    }
}
