<?php

namespace App\Tests\v2\Controller\DocumentController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ToggleVisibilityTest extends AbstractControllerTest
{
    private const TEST_EMAIL = 'documenttest@mail.com';
    private User $user;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->user = $this->createTestBeneficiary(self::TEST_EMAIL)->getUser();
    }

    protected function tearDown(): void
    {
        $this->removeTestUser(self::TEST_EMAIL);
    }

    public function testToggleVisibility(): void
    {
        // Only testedBeneficiary is logged
        $this->client->loginUser($this->user);
        $testedBeneficiary = $this->user->getSubjectBeneficiaire();
        $randomBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();

        $testedBeneficiaryDocument = DocumentFactory::createOne(['beneficiaire' => $testedBeneficiary, 'bPrive' => false])->object();
        $randomBeneficiaryDocument = DocumentFactory::createOne(['beneficiaire' => $randomBeneficiary, 'bPrive' => false])->object();

        $em = $this->getEntityManager();
        $em->persist($testedBeneficiaryDocument);
        $em->persist($randomBeneficiaryDocument);
        $em->flush();

        // testedBeneficiary try to toggle visibility of a contact from another beneficiary
        $this->client->xmlHttpRequest('PATCH', sprintf('/document/%s/toggle-visibility', $randomBeneficiaryDocument->getId()));
        $this->assertResponseStatusCodeSame(403);

        $this->client->xmlHttpRequest('PATCH', sprintf('/document/%s/toggle-visibility', $testedBeneficiaryDocument->getId()));
        $this->assertResponseStatusCodeSame(204);

        $updatedDocument = DocumentFactory::find($testedBeneficiaryDocument->getId())->object();
        $this->assertTrue($updatedDocument->getBPrive());
    }
}
