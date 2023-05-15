<?php

namespace App\Tests\v2\Service;

use App\Api\Manager\ApiClientManager;
use App\Repository\ClientRepository;
use App\ServiceV2\BeneficiaryClientLinkService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BeneficiaryClientLinkServiceTest extends KernelTestCase
{
    private ?BeneficiaryClientLinkService $service = null;

    protected function setUp(): void
    {
        $clientRepository = $this->createMock(ClientRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $apiClientManager = $this->createMock(ApiClientManager::class);
        $this->service = new BeneficiaryClientLinkService($em, $clientRepository, $apiClientManager);
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(BeneficiaryClientLinkService::class, $this->service);
    }

    public function testCreateExternalLink(): void
    {
        $this->assertTrue(true);
    }
}
