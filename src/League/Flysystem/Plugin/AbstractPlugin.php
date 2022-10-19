<?php

namespace XzHonour\AliOSS\League\Flysystem\Plugin;

use League\Flysystem\FilesystemOperator;
use XzHonour\AliOSS\League\Flysystem\PluginInterface;

abstract class AbstractPlugin implements PluginInterface
{
    /**
     * @var FilesystemOperator
     */
    protected FilesystemOperator $filesystem;

    /**
     * Set the Filesystem object.
     *
     * @param FilesystemOperator $filesystem
     */
    public function setFilesystem(FilesystemOperator $filesystem)
    {
        $this->filesystem = $filesystem;
    }
}
