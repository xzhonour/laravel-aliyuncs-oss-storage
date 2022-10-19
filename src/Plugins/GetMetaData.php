<?php

namespace XzHonour\AliOSS\Plugins;

/**
 * 获取文件元信息
 */
class GetMetaData extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return 'getMetaData';
    }

    public function handle(string $source)
    {
        return $this->adapter->getMetadata($source);
    }
}
