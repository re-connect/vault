<?php

namespace App\Tests\v2\Controller\EventController;

use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ToggleVisibilityTest extends AbstractControllerTest
{
    private KernelBrowser $client;
    public const URL = '/event/%s/toggle-visibility';

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

        $testedBeneficiaryEvent = EventFactory::createOne(['bPrive' => false, 'beneficiaire' => $testedBeneficiary])->object();
        $randomBeneficiaryEvent = EventFactory::createOne(['bPrive' => false, 'beneficiaire' => $randomBeneficiary])->object();

        $this->client->xmlHttpRequest('PATCH', sprintf('/event/%s/toggle-visibility', $randomBeneficiaryEvent->getId()));
        $this->assertResponseStatusCodeSame(403);

        $this->client->xmlHttpRequest('PATCH', sprintf('/event/%s/toggle-visibility', $testedBeneficiaryEvent->getId()));
        $this->assertResponseStatusCodeSame(204);

        $updatedContact1 = EventFactory::find($testedBeneficiaryEvent->getId())->object();
        $this->assertTrue($updatedContact1->getBPrive());
    }
}
