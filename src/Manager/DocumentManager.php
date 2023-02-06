<?php

namespace App\Manager;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\File\File;

class DocumentManager
{
    private S3Client $client;
    private string $bucketName;
    private LoggerInterface $logger;

    public function __construct(string $bucketName, string $bucketEndpoint, string $bucketAccess, string $bucketSecret, LoggerInterface $logger)
    {
        $this->client = new S3Client([
            'endpoint' => $bucketEndpoint,
            'credentials' => [
                'key' => $bucketAccess,
                'secret' => $bucketSecret,
            ],
            'use_path_style_endpoint' => true,
            'region' => 'eu-west-1',
            'version' => 'latest',
        ]);
        $this->bucketName = $bucketName;
        $this->logger = $logger;
    }

    /**
     * @param string $fileKey
     *
     * @return string
     */
    public function getPresignedUrl($fileKey)
    {
        try {
            $command = $this->client->getCommand('GetObject', [
                'Bucket' => $this->bucketName,
                'Key' => $fileKey,
            ]);

            return (string) $this->client->createPresignedRequest($command, '+10 minutes')->getUri();
        } catch (S3Exception $e) {
            throw $e;
        }
    }

    /**
     * @return false|resource
     */
    public function getObjectStream(string $fileKey)
    {
        $this->client->registerStreamWrapper();

        try {
            return fopen(sprintf('s3://%s/%s', $this->bucketName, $fileKey), 'r');
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @return UuidInterface
     *
     * @throws \Exception
     */
    public function putFile(File $file)
    {
        try {
            $handle = fopen($file->getPathname(), 'r');
            $key = Uuid::uuid4();
            $this->client->putObject([
                'Bucket' => $this->bucketName,
                'Key' => $key,
                'Body' => $handle,
                'ContentType' => $file->getMimeType(),
                'ACL' => 'public-read',
            ]);
            fclose($handle);

            return $key;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function deleteFile(string $key): void
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucketName,
                'Key' => $key,
            ]);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('There has been an error deleting file with key %s in the bucket, cause: %s', $key, $e->getMessage()));
        }
    }
}
