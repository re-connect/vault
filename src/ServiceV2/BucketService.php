<?php

namespace App\ServiceV2;

use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;

class BucketService
{
    private S3Client $client;

    public function __construct(
        private readonly string $bucketName,
        string $bucketEndpoint,
        string $bucketAccess,
        string $bucketSecret,
        private readonly LoggerInterface $logger
    ) {
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
    }

    /**
     * @throws \Exception
     */
    public function deleteFile(string $key): void
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucketName,
                'Key' => $key,
            ]);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('There has been an error deleting file with key %s in the bucket, cause: %s', $key, $e->getMessage()));
            throw $e;
        }
    }

    /**
     * @return bool|resource
     */
    public function getObjectStream(string $fileKey)
    {
        $this->client->registerStreamWrapper();

        try {
            return fopen(sprintf('s3://%s/%s', $this->bucketName, $fileKey), 'r');
        } catch (\Exception $e) {
            $this->logger->error(sprintf('There has been an error downloading file with key %s, cause: %s', $fileKey, $e->getMessage()));

            return false;
        }
    }
}
