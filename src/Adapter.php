<?php

namespace XzHonour\AliOSS;

use Aws\Api\DateTimeResult;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\AwsS3V3\VisibilityConverter;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Illuminate\Support\Facades\Log;
use OSS\Core\OssException;
use OSS\OssClient;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Throwable;

class Adapter implements FilesystemAdapter
{
    /**
     * @var OssClient oss client
     */
    private $client;

    /**
     * @var string bucket name
     */
    private $bucket;

    /**
     * @var string endpoint link
     */
    private $endPoint;

    /**
     * @var string url schema
     */
    private $schema;

    /**
     * @var string custom domain for show
     */
    private $customDomain;

    /**
     * @var array options
     */
    private $options = [];

    /**
     * @var PathPrefixer
     */
    protected PathPrefixer $prefixer;

    /**
     * @var MimeTypeDetector
     */
    private MimeTypeDetector $mimeTypeDetector;

    /**
     * View Aliyun OSS Setting.
     *
     * @var array
     */
    protected static $optionsMap = [
        'mimetype' => OssClient::OSS_CONTENT_TYPE, // alias of content-type.
        'content-type' => OssClient::OSS_CONTENT_TYPE,
        'content-length' => OssClient::OSS_LENGTH,
        'content-encoding' => 'Content-Encoding',
        'content-language' => 'Content-Language',
        'content-disposition' => OssClient::OSS_CONTENT_DISPOSTION,
        'cache-control' => OssClient::OSS_CACHE_CONTROL,
        'expires' => OssClient::OSS_EXPIRES,
        'visibility' => OssClient::OSS_OBJECT_ACL,
    ];

    public function __construct(OssClient $client, $bucket, $endPoint, $schema, $customDomain, $prefix = '')
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->endPoint = $endPoint;
        $this->schema = $schema;
        $this->customDomain = $customDomain;
        $this->prefixer = new PathPrefixer($prefix);
        $this->mimeTypeDetector = new FinfoMimeTypeDetector();
   }

    /**
     * write.
     *
     * @param string $path
     * @param string $contents
     *
     * @param  Config  $config
     * @return void
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $path = $this->prefixer->prefixPath($path);

        $options = $this->getOptions($config);

        if (! isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = $this->mimeTypeDetector->detectMimeType($path,$contents);
        }

        if (! isset($options[OssClient::OSS_LENGTH])) {
            $options[OssClient::OSS_LENGTH] = is_resource($contents) ? $this->getStreamSize($contents) : $this->contentSize($contents);
        }

        if ($options[OssClient::OSS_LENGTH] === null) {
            unset($options['ContentLength']);
        }

        try {
            $this->client->putObject($this->bucket, $path, $contents, $options);
        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);

            throw UnableToWriteFile::atLocation($path, $t->getMessage(), $t);
        }
    }

    /**
     * Get content size.
     *
     * @param string $contents
     *
     * @return int content size
     */
    public static function contentSize(string $contents): int
    {
        return defined('MB_OVERLOAD_STRING') ? mb_strlen($contents, '8bit') : strlen($contents);
    }

    /**
     * Get the size of a stream.
     *
     * @param resource $resource
     *
     * @return int|null stream size
     */
    public function getStreamSize($resource): ?int
    {
        $stat = fstat($resource);

        if ( ! is_array($stat) || ! isset($stat['size'])) {
            return null;
        }

        return $stat['size'];
    }

    /**
     * Write stream.
     *
     * @param  string  $path
     * @param  resource $contents
     * @param  Config  $config
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $resources = stream_get_contents($contents);
        $this->write($path, $resources, $config);
    }

    /**
     * Copy.
     *
     * @param string $source
     * @param string $destination
     *
     * @return bool
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        $path = $this->prefixer->prefixPath($source);
        $newpath = $this->prefixer->prefixPath($destination);

        try {
            $this->client->copyObject($this->bucket, $path, $this->bucket, $newpath);
        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);
            throw new OssException($t->getMessage());
        }
    }

    /**
     * Delete.
     *
     * @param string $path
     *
     * @return void
     */
    public function delete(string $path): void
    {
        $path = $this->prefixer->prefixPath($path);

        try {
            $this->client->deleteObject($this->bucket, $path);
        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);
            throw new OssException($t->getMessage());
        }
    }


    /**
     * Create dir. Usually pre-create on Aliyun server.
     *
     * @param  string  $dirname
     *
     * @param  Config  $config
     * @return array|bool|false
     */
    public function createDirectory($dirname, Config $config): void
    {
        $path = $this->prefixer->prefixPath($dirname);

        $options = $this->getOptions($config);

        try {
            $this->client->createObjectDir($this->bucket, $path, $options);
        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);

            throw new OssException($t->getMessage());
        }
    }

    /**
     * Set visibility.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return void
     */
    public function setVisibility($path, $visibility): void
    {
        $path = $this->prefixer->prefixPath($path);

        $acl = (Visibility::PUBLIC === $visibility) ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;

        try {
            $this->client->putObjectAcl($this->bucket, $path, $acl);

        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);
            throw UnableToSetVisibility::atLocation($path, $t->getMessage(), $t);
        }
    }

    /**
     * Check exist. Notice, it may throw exception.
     *
     * @param string $path
     *
     * @return bool
     *@throws UnableToCheckExistence
     *
     */
    public function fileExists(string $path): bool
    {
        $path = $this->prefixer->prefixPath($path);
        try {
            return $this->client->doesObjectExist($this->bucket, $path);
        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);
            throw UnableToCheckFileExistence::forLocation($path, $t);
        }
    }

    /**
     * Read.
     *
     * @param string $path
     *
     * @return string
     */
    public function read(string $path): string
    {
        $path = $this->prefixer->prefixPath($path);

        try {
            return $this->client->getObject($this->bucket, $path);
        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);
            throw new OssException($t->getMessage());
        }
    }

    /**
     * Read stream.
     *
     * @param string $path
     *
     * @return resource
     */
    public function readStream(string $path)
    {
        try {
            return fopen($this->getUrl($path), 'r');
        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);
            throw new FileException($t->getMessage());
        }
    }

    /**
     * List contents.
     *
     * @notice 仅列取指定目录文件
     *
     * @param string $path
     * @param bool $deep
     * @param array $options
     *
     * @return iterable
     */
    public function listContents(string $path, bool $deep, array $options = []): iterable
    {
        $prefix = trim($this->prefixer->prefixPath($path), '/');
        $prefix = empty($prefix) ? '' : $prefix . '/';
        $options = array_merge($options, ['prefix' => $prefix]);

        if ($deep === false) {
            $options['Delimiter'] = '/';
        }
        try {
            return $this->client->listObjects($this->bucket, $options)->getObjectList();
        }catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);
            throw new FileException($t->getMessage());
        }
    }

    /**
     * Get metadata.
     *
     * @param string $path
     *
     * @return array
     */
    public function getMetadata(string $path): array
    {
        $path = $this->prefixer->prefixPath($path);

        try {
            return $this->client->getObjectMeta($this->bucket, $path);
        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);
            throw new OssException($t->getMessage());
        }
    }

    /**
     * @param array $metadata
     * @param string $path
     * @return FileAttributes|DirectoryAttributes
     * @throws \Exception
     */
    public function mapOssObjectMetadata(array $metadata, string $path): FileAttributes|DirectoryAttributes
    {
        if (substr($path, -1) === '/') {
            return new DirectoryAttributes(rtrim($path, '/'));
        }

        $mimetype = $metadata['content-type'] ?? null;
        $fileSize = $metadata['content-length'] ?? null;
        $fileSize = $fileSize === null ? null : (int) $fileSize;
        $dateTime = $metadata['last-modified'] ? new DateTimeResult($metadata['last-modified']) : null;
        $lastModified = $dateTime instanceof DateTimeResult ? $dateTime->getTimeStamp() : null;

        return new FileAttributes(
            $path,
            $fileSize,
            null,
            $lastModified,
            $mimetype,
            $metadata
        );
    }

    /**
     * Get mimetype.
     *
     * @param string $path
     *
     * @return FileAttributes
     */
    public function mimetype(string $path): FileAttributes
    {
        $mimetype = $this->mapOssObjectMetadata($this->getMetadata($path), $path);

        if ($mimetype->mimeType() === null){
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return $mimetype;
    }

    /**
     * Get visibility.
     *
     * @param string $path
     *
     * @return FileAttributes
     */
    public function visibility(string $path): FileAttributes
    {
        $path = $this->prefixer->prefixPath($path);

        try {
            $acl = $this->client->getObjectAcl($this->bucket, $path);

            if (OssClient::OSS_ACL_TYPE_PUBLIC_READ == $acl || OssClient::OSS_ACL_TYPE_PUBLIC_READ_WRITE == $acl) {
                $acl = Visibility::PUBLIC;
            } else {
                $acl = Visibility::PRIVATE;
            }

            return new FileAttributes($path, null, $acl);
        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);
            throw new OssException($t->getMessage());
        }
    }

    /**
     * For Storage's `url` method.
     *
     * @param $path
     *
     * @return string
     */
    public function getUrl($path): string
    {
        $path = $this->prefixer->prefixPath($path);

        $domain = $this->customDomain ?: ($this->getSchema().$this->bucket.'.'.$this->endPoint);

        return $domain.'/'.$path;
    }

    /**
     * Get schema for url.
     *
     * @return string
     */
    protected function getSchema(): string
    {
        if ('http' == $this->schema || 'https' == $this->schema) {
            return $this->schema.'://';
        } elseif ('both' == $this->schema) {
            return '//';
        }

        return 'https://';
    }

    /**
     * Get temporary url. For Storage's `temporaryUrl` method.
     * Same with `signUrl` method.
     *
     * @param $path
     * @param $expiration
     * @param $options
     *
     * @return bool|string
     */
    public function getTemporaryUrl($path, $expiration, array $options = []): bool|string
    {
        if (! ($expiration = now()->diffInSeconds($expiration))) {
            return false;
        }

        $path = $this->prefixer->prefixPath($path);

        try {
            $method = isset($options['method']) ?: OssClient::OSS_HTTP_GET;
            $method = strtoupper($method);

            return $this->client->signUrl($this->bucket, $path, $expiration, $method, $options);
        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);

            return false;
        }
    }

    /**
     * Get options for a AliOSS call.
     *
     * @param Config $config
     *
     * @return array aliOSS options
     */
    protected function getOptions(Config $config = null)
    {
        $options = $this->options;

        if ($config) {
            $options = array_merge($options, $this->getOptionsFromConfig($config));
        }

        if(isset($options[OssClient::OSS_OBJECT_ACL])) {
            $options[OssClient::OSS_HEADERS] = [
                OssClient::OSS_OBJECT_ACL => $options[OssClient::OSS_OBJECT_ACL]
            ];

            unset($options[OssClient::OSS_OBJECT_ACL]);
        }

        return $options;
    }

    /**
     * Retrieve options from a Config instance.
     *
     * @description In face, the commonest config setting is `visibility` and `mimetype`.
     *
     * @param  \League\Flysystem\Config  $config
     * @return array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = [];

        $metas = array_keys(static::$optionsMap);

        foreach ($metas as $option) {
            if (! $config->get($option)) {
                continue;
            }

            $options[static::$optionsMap[$option]] = $config->get($option);
        }

        if ($visibility = $config->get('visibility')) {
            $options[OssClient::OSS_OBJECT_ACL] = Visibility::PUBLIC === $visibility ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;
        }

        return $options;
    }

    /**
     * Log exception info for debug.
     *
     * @param string    $fun
     * @param Throwable $t
     */
    protected function logException($fun, $t)
    {
        /* @var Throwable $t*/
        Log::error('Aliyun OSS Error', [
            'function' => $fun,
            'message' => $t->getMessage(),
            'file' => $t->getFile().':'.$t->getLine(),
        ]);
    }

    /**
     * Get oss client. For plugin uses.
     *
     * @return \OSS\OssClient
     */
    public function getOssClient()
    {
        return $this->client;
    }

    /**
     * Get bucket. For plugin uses.
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @throws UnableToMoveFile
     * @throws FilesystemException
     */
    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (Throwable $t) {
            $this->logException(__FUNCTION__, $t);
            throw new OssException($t->getMessage());
        }
    }

    /**
     * @notice Return to true forever
     * @notice 期待阿里云oss支持
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     */
    public function directoryExists(string $path): bool
    {
        return true;
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function fileSize(string $path): FileAttributes
    {
        $filesize = $this->mapOssObjectMetadata($this->getMetadata($path), $path);

        if ($filesize->fileSize() === null){
            throw UnableToRetrieveMetadata::lastModified($path);
        }

        return $filesize;
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function lastModified(string $path): FileAttributes
    {
        $lastModified = $this->mapOssObjectMetadata($this->getMetadata($path), $path);

        if ($lastModified->lastModified() === null){
            throw UnableToRetrieveMetadata::lastModified($path);
        }

        return $lastModified;
    }

    /**
     * Delete dir.
     *
     * @notice 待补充
     *
     * @param string $path
     *
     * @return bool
     * @throws UnableToDeleteDirectory
     * @throws FilesystemException
     */
    public function deleteDirectory(string $path): void
    {
        // todo
    }

}
