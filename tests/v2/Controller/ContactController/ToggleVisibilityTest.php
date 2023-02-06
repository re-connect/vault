<?php

namespace App\Tests\v2\Controller\ContactController;

use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ToggleVisibilityTest extends AbstractControllerTest
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        static::ensureKernelShutdown();
        $this->client = static::createClient();
    }

    public function testToggleVisibility(): void
    {
        [$testedBeneficiary, $randomBeneficiary] = BeneficiaireFactory::randomSet(2);
        $this->client->loginUser($testedBeneficiary->getUser());

        $testedBeneficiaryContact = ContactFactory::createOne(['bPrive' => false, 'beneficiaire' => $testedBeneficiary])->object();
        $randomBeneficiaryContact = ContactFactory::createOne(['bPrive' => false, 'beneficiaire' => $randomBeneficiary])->object();

        $this->client->xmlHttpRequest('PATCH', sprintf('/contact/%s/toggle-visibility', $randomBeneficiaryContact->getId()));
        $this->assertResponseStatusCodeSame(403);

        $this->client->xmlHttpRequest('PATCH', sprintf('/contact/%s/toggle-visibility', $testedBeneficiaryContact->getId()));
        $this->assertResponseStatusCodeSame(204);

        $updatedContact = ContactFactory::find($testedBeneficiaryContact->getId())->object();
        $this->assertTrue($updatedContact->getBPrive());
    }
}
