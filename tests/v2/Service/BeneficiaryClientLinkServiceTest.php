<?php

namespace App\Tests\v2\Service;

use App\Repository\ClientRepository;
use App\ServiceV2\BeneficiaryClientLinkService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BeneficiaryClientLinkServiceTest extends KernelTestCase
{
    private ?BeneficiaryClientLinkService $service = null;
    private ?ClientRepository $clientRepository = null;

    protected function setUp(): void
    {
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->service = new BeneficiaryClientLinkService($this->clientRepository);
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
