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

    public function testMoveToWrongBeneficiary(): void
    {
        $clientTest = static::createClient();
        $user = $this->getTestUserFromDb(BeneficiaryFixture::BENEFICIARY_MAIL);
        $clientTest->loginUser($user);

        $testedBeneficiary = $user->getSubjectBeneficiaire();
        $randomBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS)->object();

        $testedBeneficiaryDocument = DocumentFactory::createOne(['beneficiaire' => $testedBeneficiary, 'bPrive' => false])->object();
        $randomBeneficiaryFolder = FolderFactory::createOne(['beneficiaire' => $randomBeneficiary])->object();

        // Tested beneficiary tries to move document inside random beneficiarie's folder
        $clientTest->request('GET', sprintf(self::URL, $testedBeneficiaryDocument->getId(), $randomBeneficiaryFolder->getId()));
        self::assertResponseStatusCodeSame(403);
    }

    public function provideTestMoveToFolder(): ?\Generator
    {
        yield 'Shared document should be hydrated with parent visibility' => [false];
        yield 'Private document should be hydrated with parent visibility' => [true];
    }

    /** @dataProvider provideTestMoveToFolder */
    public function testMoveToFolder(bool $isPrivate): void
    {
        $clientTest = static::createClient();
        $user = $this->getTestUserFromDb(BeneficiaryFixture::BENEFICIARY_MAIL);
        $clientTest->loginUser($user);
        $beneficiary = $user->getSubjectBeneficiaire();

        // Document and destination folder have different visibility
        $document = DocumentFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => $isPrivate])->object();
        $folder = FolderFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => !$isPrivate])->object();

        $clientTest->request('GET', sprintf(self::URL, $document->getId(), $folder->getId()));
        $document = DocumentFactory::find($document)->object();
        $folder = FolderFactory::find($folder)->object();

        self::assertEquals($folder->getId(), $document->getDossier()->getId());
        self::assertEquals($folder->getBPrive(), $document->getBprive());
    }
}
