<?php

namespace App\Tests\v2\Controller\DocumentController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
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
    public function testRoute(
        string $url,
        int $expectedStatusCode,
        ?string $userMail = null,
        ?string $expectedRedirect = null,
        string $method = 'GET',
        bool $isXmlHttpRequest = false,
        array $body = [],
    ): void {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $document = DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false])->object();
        $folder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false])->object();

        $url = sprintf(
            $url,
            $document->getId(),
            $folder->getId(),
        );
        $expectedRedirect = $expectedRedirect ? sprintf($expectedRedirect, $beneficiary->getId()) : '';
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        // Also check that authorized Pro can't update private data
        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES === $userMail) {
            $newDocument = DocumentFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => true])->object();
            $newFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false])->object();
            $newUrl = sprintf(
                self::URL,
                $newDocument->getId(),
                $newFolder->getId(),
            );
            $this->assertRoute($newUrl, 403, $userMail, null, $method, true);
        }
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should redirect after when authenticated as beneficiary' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary/%s/documents'];
        yield 'Should redirect after when when authenticated as member with relay in common' => [self::URL, 302, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, '/beneficiary/%s/documents'];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
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
