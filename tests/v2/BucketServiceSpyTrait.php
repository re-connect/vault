<?php

namespace App\Tests\v2;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\ServiceV2\BucketService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait BucketServiceSpyTrait
{
    /**
     * @return \ArrayObject<int, string> keys of the files deleted from the bucket during the next requests
     */
    private function spyOnDeletedFiles(KernelBrowser|Client $client): \ArrayObject
    {
        $deletedKeys = new \ArrayObject();
        $bucketService = $this->createMock(BucketService::class);
        $bucketService->method('deleteFile')->willReturnCallback(fn (string $key) => $deletedKeys->append($key));

        // The mock must survive the kernel reboot that happens between two requests
        $client->disableReboot();
        $client->getContainer()->set(BucketService::class, $bucketService);

        return $deletedKeys;
    }
}
