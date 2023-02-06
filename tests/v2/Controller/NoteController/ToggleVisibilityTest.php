<?php

namespace App\Tests\v2\Controller\NoteController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ToggleVisibilityTest extends AbstractControllerTest
{
    private const TEST_EMAIL = 'notetest@mail.com';
    private User $user;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->user = $this->createTestBeneficiary(self::TEST_EMAIL)->getUser();
    }

    public function testToggleVisibility(): void
    {
        // Only testedBeneficiary is logged
        $this->client->loginUser($this->user);
        $testedBeneficiary = $this->user->getSubjectBeneficiaire();
        $randomBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();

        $testedBeneficiaryNote = NoteFactory::createOne(['beneficiaire' => $testedBeneficiary, 'bPrive' => false])->object();
        $randomBeneficiaryNote = NoteFactory::createOne(['beneficiaire' => $randomBeneficiary, 'bPrive' => false])->object();

        $em = $this->getEntityManager();
        $em->persist($testedBeneficiaryNote);
        $em->persist($randomBeneficiaryNote);
        $em->flush();

        // testedBeneficiary try to toggle visibility of a contact from another beneficiary
        $this->client->xmlHttpRequest('PATCH', sprintf('/note/%s/toggle-visibility', $randomBeneficiaryNote->getId()));
        $this->assertResponseStatusCodeSame(403);

        $this->client->xmlHttpRequest('PATCH', sprintf('/note/%s/toggle-visibility', $testedBeneficiaryNote->getId()));
        $this->assertResponseStatusCodeSame(204);

        $updatedNote = NoteFactory::find($testedBeneficiaryNote->getId())->object();
        $this->assertTrue($updatedNote->getBPrive());
    }
}
