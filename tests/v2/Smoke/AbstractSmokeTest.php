<?php

namespace App\Tests\v2\Smoke;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Beneficiaire;
use App\Tests\Factory\BeneficiaireFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

abstract class AbstractSmokeTest extends WebTestCase
{
    use Factories;
    protected KernelBrowser $client;
    protected ?Beneficiaire $beneficiary;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $this->client->loginUser($this->beneficiary->getUser());

        parent::setUp();
    }

    public function assertRoute(string $url): void
    {
        $this->client->request('GET', $url);
        $this->assertLessThan(400, $this->client->getResponse()->getStatusCode());
    }
}
