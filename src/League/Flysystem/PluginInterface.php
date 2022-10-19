<?php

namespace XzHonour\AliOSS\League\Flysystem;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;

interface PluginInterface
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * Set the Filesystem object.
     *
     * @param FilesystemOperator $filesystem
     */
    public function setFilesystem(FilesystemOperator $filesystem);
}
