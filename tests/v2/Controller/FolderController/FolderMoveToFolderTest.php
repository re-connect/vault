<?php

namespace App\Tests\v2\Controller\FolderController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class FolderMoveToFolderTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/folder/%s/move-to-folder/%s';

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
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $parentFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false])->_real();
        $subFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false])->_real();

        $url = sprintf(
            $url,
            $subFolder->getId(),
            $parentFolder->getId(),
        );
        $expectedRedirect = $expectedRedirect ? sprintf($expectedRedirect, $beneficiary->getId()) : '';
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES === $userMail) {
            $privateParentFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => true])->_real();
            $subFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false])->_real();
            $newUrl = sprintf(
                self::URL,
                $subFolder->getId(),
                $privateParentFolder->getId(),
            );
            $this->assertRoute($newUrl, 403, $userMail, null, $method, true);
        }
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should redirect after when authenticated as beneficiary' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary/%s/documents'];
        yield 'Should redirect after when authenticated as member with relay in common' => [self::URL, 302, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, '/beneficiary/%s/documents'];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
    }

    public function testShouldNotMoveToOtherBeneficiary(): void
    {
        $clientTest = static::createClient();
        $user = UserFactory::find(['email' => BeneficiaryFixture::BENEFICIARY_MAIL])->_real();
        $clientTest->loginUser($user);

        $testedBeneficiary = $user->getSubjectBeneficiaire();
        $randomBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);
        $folder = FolderFactory::createOne(['beneficiaire' => $testedBeneficiary, 'bPrive' => false])->_real();
        $randomFolder = FolderFactory::createOne(['beneficiaire' => $randomBeneficiary])->_real();

        // Tested beneficiary tries to move folder inside random beneficiarie's folder
        $clientTest->request('GET', sprintf(self::URL, $folder->getId(), $randomFolder->getId()));
        self::assertResponseStatusCodeSame(403);
    }

    public function testCanNotMoveParentFolderIntoChild(): void
    {
        $errorMessage = "ERREUR Ce mouvement de dossier n'est pas valide";
        $clientTest = static::createClient();
        $clientTest->followRedirects();
        $user = UserFactory::find(['email' => BeneficiaryFixture::BENEFICIARY_MAIL])->_real();
        $clientTest->loginUser($user);
        $beneficiary = $user->getSubjectBeneficiaire();

        $parentFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary])->_real();
        $childFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'dossierParent' => $parentFolder])->_real();
        $grandChildFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'dossierParent' => $childFolder])->_real();

        self::assertNull($parentFolder->getDossierParent());
        self::assertSame($parentFolder, $childFolder->getDossierParent());
        self::assertSame($childFolder, $grandChildFolder->getDossierParent());

        // Test error moving parent in child
        $clientTest->request('GET', sprintf(self::URL, $parentFolder->getId(), $childFolder->getId()));
        $this->assertSelectorTextContains('div.alert-dismissible', $errorMessage);
        self::assertSame($parentFolder, $childFolder->getDossierParent());

        // Test error moving parent in grandChild
        $clientTest->request('GET', sprintf(self::URL, $parentFolder->getId(), $childFolder->getId()));
        $this->assertSelectorTextContains('div.alert-dismissible', $errorMessage);
        self::assertSame($childFolder, $grandChildFolder->getDossierParent());
    }

    public function provideTestMoveToFolder(): ?\Generator
    {
        yield 'Shared folder moved in shared folder should be shared' => [false, false, false];
        yield 'Shared folder moved in private folder should be private' => [false, true, true];
        yield 'Private folder moved in shared folder should be private' => [true, false, true];
        yield 'Private folder moved in private folder should be private' => [true, true, true];
    }

    /** @dataProvider provideTestMoveToFolder */
    public function testMoveToFolder(bool $isPrivateFolder, bool $isPrivateParentFolder, bool $shouldBePrivate): void
    {
        $clientTest = static::createClient();
        $user = UserFactory::find(['email' => BeneficiaryFixture::BENEFICIARY_MAIL])->_real();
        $clientTest->loginUser($user);

        $testedBeneficiary = $user->getSubjectBeneficiaire();
        $parentFolder = FolderFactory::createOne(['beneficiaire' => $testedBeneficiary, 'bPrive' => $isPrivateFolder])->_real();
        $subFolder = FolderFactory::createOne(['beneficiaire' => $testedBeneficiary, 'bPrive' => $isPrivateParentFolder])->_real();

        $clientTest->request('GET', sprintf(self::URL, $subFolder->getId(), $parentFolder->getId()));
        $parentFolder = FolderFactory::find(['id' => $parentFolder->getId()]);
        $subFolder = FolderFactory::find(['id' => $subFolder->getId()]);
        self::assertEquals($parentFolder->_real()->getSousDossiers()->last()->getId(), $subFolder->_real()->getId());
        self::assertEquals($shouldBePrivate, $subFolder->_real()->getBprive());

        $subFolder->remove();
        $parentFolder->remove();
    }
}
