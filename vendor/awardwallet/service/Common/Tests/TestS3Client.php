<?php

namespace AwardWallet\Common\Tests;

use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Psr7\Stream as GuzzleStream;

class TestS3Client extends S3Client
{
    /**
     * @var array
     */
    protected $files = [];
    /**
     * @var string
     */
    protected $tmpBucketsDir;

    public function __construct($tmpBucketsDir)
    {
        $this->tmpBucketsDir = rtrim($tmpBucketsDir, '/');
    }

    public function clear()
    {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $this->files = [];
    }

    public function upload(
        $bucket,
        $key,
        $body,
        $acl = 'private',
        array $options = []
    ) {
        $this->putObject([
            'Bucket' => $bucket,
            'Key' => $key,
            'Body' => $body
        ]);
    }


    public function putObject(array $args = [])
    {
        $this->createBucketIfNotExists($args['Bucket']);
        file_put_contents($newSource = $this->generateFilenameByArgs($args), $args['Body']);
        $this->files[] = $newSource;
    }

    protected function createBucketIfNotExists($bucket)
    {
        if (!file_exists($this->tmpBucketsDir)) {
            mkdir($this->tmpBucketsDir);
        }

        if (!file_exists("{$this->tmpBucketsDir}/{$bucket}")) {
            mkdir("{$this->tmpBucketsDir}/{$bucket}");
        }

        return "{$this->tmpBucketsDir}/{$bucket}";
    }

    protected function generateFilenameByArgs(array $args)
    {
        return "{$this->tmpBucketsDir}/{$args['Bucket']}/{$args['Key']}";
    }

    public function copyObject(array $args = [])
    {
        if (
            !file_exists($source = "{$this->tmpBucketsDir}/{$args['CopySource']}") ||
            !is_file($source)
        ) {
            throw self::createS3Exception('No such file to copy');
        }

        $this->createBucketIfNotExists($args['Bucket']);
        copy(
            $source,
            $newSource = $this->generateFilenameByArgs($args)
        );
        $this->files[] = $newSource;
    }

    protected static function createS3Exception(string $error): S3Exception
    {
        return new S3Exception($error, new class($error) extends ArrayCollection implements CommandInterface {
            protected $name;

            public function __construct($error)
            {
                $this->name = $error;
            }

            public function getName() {
                return $this->name;
            }

            public function hasParam($name) {
                return false;
            }

            public function getHandlerList()
            {
                return [];
            }

        });
    }

    public function deleteObject(array $args = [])
    {
        if (
            !file_exists($source = $this->generateFilenameByArgs($args)) ||
            !is_file($source)
        ) {
            throw self::createS3Exception('No such file to delete, "' . $source . '"');
        }

        unlink($source);
    }

    public function getObject(array $args = [])
    {
        if (
            !file_exists($source = $this->generateFilenameByArgs($args)) ||
            !is_file($source)
        ) {
            throw self::createS3Exception('No such file to get');
        }

        return new Result(['Body' => new GuzzleStream(fopen($source, 'r'))]);
    }

    public function headObject(array $args = [])
    {
        if (
            !file_exists($source = $this->generateFilenameByArgs($args)) ||
            !is_file($source)
        ) {
            throw self::createS3Exception('No such file to head');
        }
    }

    public function deleteMatchingObjects($bucket, $prefix = '', $regex = '', array $options = [])
    {
        $this->createBucketIfNotExists($bucket);

        foreach (glob("{$this->tmpBucketsDir}/{$bucket}/{$prefix}*") as $file) {
            if (preg_match($regex, basename($file))) {
                unlink($file);
            }
        }
    }

    public function getIterator($name, array $args = [])
    {
        return [];
    }

    protected function rmdirRecursive($dir)
    {
        foreach (glob("{$dir}/*") as $file) {
            if (is_dir($file)) {
                $this->rmdirRecursive($file);
            } else {
                unlink($file);
            }
        }

        rmdir($dir);
    }

}