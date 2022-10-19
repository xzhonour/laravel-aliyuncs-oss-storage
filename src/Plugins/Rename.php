<?php

namespace XzHonour\AliOSS\Plugins;

class Rename extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return 'rename';
    }

    public function handle(string $source, string $destination)
    {
        $this->adapter->copy($source, $destination, $this->filesystem->getConfig());
        $this->adapter->delete($source);
    }
}
