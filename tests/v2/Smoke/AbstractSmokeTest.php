<?php

namespace App\Tests\v2\Smoke;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Beneficiaire;
use App\Entity\Membre;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

abstract class AbstractSmokeTest extends WebTestCase
{
    use Factories;
    protected KernelBrowser $client;
    protected ?Beneficiaire $beneficiary;
    protected ?Membre $professional;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $this->professional = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES)->object();
        parent::setUp();
    }

    public function assertRoute(string $url): void
    {
        $this->client->request('GET', $url);
        $this->assertLessThan(400, $this->client->getResponse()->getStatusCode());
    }
}
