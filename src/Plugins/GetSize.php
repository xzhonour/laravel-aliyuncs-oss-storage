<?php

namespace XzHonour\AliOSS\Plugins;

/**
 * 获取文件大小
 */
class GetSize extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return 'getSize';
    }

    public function handle(string $source)
    {
        if ($metadata = $this->adapter->getMetadata($source)) {
            return $metadata['content-length'];
        }

        return -1;
    }
}
