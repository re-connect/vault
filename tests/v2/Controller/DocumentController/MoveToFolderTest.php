<?php

namespace App\Tests\v2\Controller\DocumentController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class MoveToFolderTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/documents/%d/move/folder/%d';

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $beneficiary->addDocument(DocumentFactory::createOne(['beneficiaire' => $beneficiary])->object());
        $beneficiary->addDossier(FolderFactory::createOne(['beneficiaire' => $beneficiary])->object());

        $url = sprintf(
            $url,
            $beneficiary->getDocuments()->last()->getId(),
            $beneficiary->getDossiers()->last()->getId(),
        );
        $expectedRedirect = $expectedRedirect ? sprintf($expectedRedirect, $beneficiary->getId()) : '';
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should redirect after when authenticated as beneficiary' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary/%s/documents'];
        yield 'Should return 403 status code when authenticated as an other beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
    }

    public function testMoveToFolder(): void
    {
        $clientTest = static::createClient();
        $user = UserFactory::find(['email' => BeneficiaryFixture::BENEFICIARY_MAIL])->object();
        $clientTest->loginUser($user);

        $testedBeneficiary = $user->getSubjectBeneficiaire();
        $randomBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS)->object();

        $testedBeneficiaryDocument = DocumentFactory::createOne(['beneficiaire' => $testedBeneficiary, 'bPrive' => false])->object();
        $testedBeneficiaryFolder = FolderFactory::createOne(['beneficiaire' => $testedBeneficiary, 'bPrive' => true])->object();
        $randomBeneficiaryFolder = FolderFactory::createOne(['beneficiaire' => $randomBeneficiary])->object();

        // Tested beneficiary tries to move document inside random beneficiarie's folder
        $clientTest->request('GET', sprintf(self::URL, $testedBeneficiaryDocument->getId(), $randomBeneficiaryFolder->getId()));
        self::assertResponseStatusCodeSame(403);

        // Then move in a folder that belongs to him
        $clientTest->request('GET', sprintf(self::URL, $testedBeneficiaryDocument->getId(), $testedBeneficiaryFolder->getId()));
        $testedBeneficiaryDocument = DocumentFactory::find(['id' => $testedBeneficiaryDocument->getId()]);
        $testedBeneficiaryFolder = FolderFactory::find(['id' => $testedBeneficiaryFolder->getId()]);

        self::assertEquals($testedBeneficiaryDocument->object()->getDossier()->getId(), $testedBeneficiaryFolder->object()->getId());
        self::assertTrue($testedBeneficiaryFolder->object()->getbPrive());
        $testedBeneficiaryDocument->remove();
        $testedBeneficiaryFolder->remove();
    }
}
