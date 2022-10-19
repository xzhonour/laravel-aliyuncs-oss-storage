<?php

namespace XzHonour\AliOSS\Plugins;

use League\Flysystem\Config;

class PutRemoteFile extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return 'putRemoteFile';
    }

    public function handle($object, $remoteUrl, array $options = [])
    {
        $resource = fopen($remoteUrl, 'r');

        $this->adapter->writeStream($object, $resource, new Config($options));
    }
}
