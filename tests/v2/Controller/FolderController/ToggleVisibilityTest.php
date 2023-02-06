<?php

namespace App\Tests\v2\Controller\FolderController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ToggleVisibilityTest extends AbstractControllerTest
{
    private const TEST_EMAIL = 'foldertest@mail.com';
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

        $testedBeneficiaryFolder = FolderFactory::createOne(['beneficiaire' => $testedBeneficiary, 'bPrive' => false])->object();
        $randomBeneficiaryFolder = FolderFactory::createOne(['beneficiaire' => $randomBeneficiary, 'bPrive' => false])->object();
        $testedBeneficiaryDocument = DocumentFactory::createOne([
            'beneficiaire' => $randomBeneficiary,
            'bPrive' => false,
            'dossier' => $testedBeneficiaryFolder,
        ])->object();

        // testedBeneficiary try to toggle visibility of a folder from another beneficiary
        $this->client->xmlHttpRequest('PATCH', sprintf('/folder/%s/toggle-visibility', $randomBeneficiaryFolder->getId()));
        $this->assertResponseStatusCodeSame(403);

        $this->client->xmlHttpRequest('PATCH', sprintf('/folder/%s/toggle-visibility', $testedBeneficiaryFolder->getId()));
        $this->assertResponseStatusCodeSame(204);

        $updatedFolder = FolderFactory::find($testedBeneficiaryFolder->getId())->object();
        $updatedDocument = DocumentFactory::find($testedBeneficiaryDocument->getId())->object();

        $this->assertTrue($updatedFolder->getBPrive());
        $this->assertTrue($updatedDocument->getBPrive());
    }
}
