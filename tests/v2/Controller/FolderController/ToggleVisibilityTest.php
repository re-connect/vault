<?php

namespace App\Tests\v2\Controller\FolderController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class ToggleVisibilityTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/folder/%s/toggle-visibility';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login', 'PATCH'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL, null, 'PATCH'];
        yield 'Should return 200 status when authenticated as member with relay in common' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, null, 'PATCH'];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, null, 'PATCH'];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL, null, 'PATCH'];
    }

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
        $publicFolder = FolderFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => false])->object();
        $url = sprintf($url, $publicFolder->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method, true);

        // Also check that authorized Pro can't update private data
        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES === $userMail) {
            $privateFolder = FolderFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => true])->object();
            $newUrl = sprintf(self::URL, $privateFolder->getId());
            $this->assertRoute($newUrl, 403, $userMail, null, $method, true);
        }
    }

    public function provideTestVisibilityIsToggledRecursively(): ?\Generator
    {
        yield 'Toggle visibility should hydrate all childs with private folder' => [true];
        yield 'Toggle visibility should hydrate all childs with shared folder' => [false];
    }

    /** @dataProvider provideTestVisibilityIsToggledRecursively */
    public function testVisibilityIsToggledRecursively(bool $isPrivate): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        // We create 1 folder with 1 child folder that contains 2 documents
        $folder = FolderFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => $isPrivate])->object();
        $childFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => $isPrivate, 'dossierParent' => $folder])->object();
        $firstDocument = DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => $isPrivate, 'dossier' => $childFolder])->object();
        // Second document does not have the same visibility as parent folder, this case should not occur, but we need to make sure that visibility is toggled only if childen visibility is different
        $secondDocumentWithWrongVisibility = DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => !$isPrivate, 'dossier' => $childFolder])->object();

        $this->assertRoute(
            sprintf(self::URL, $folder->getId()),
            200, BeneficiaryFixture::BENEFICIARY_MAIL,
            null,
            'PATCH',
            true,
        );

        $publicFolderVisibility = FolderFactory::find($folder)->object()->getBprive();
        $childFolderVisibility = FolderFactory::find($childFolder)->object()->getBprive();
        $firstDocumentVisibility = DocumentFactory::find($firstDocument)->object()->getBprive();
        $secondDocumentVisibility = DocumentFactory::find($secondDocumentWithWrongVisibility)->object()->getBprive();

        self::assertEquals(!$isPrivate, $publicFolderVisibility);
        self::assertEquals($childFolderVisibility, $publicFolderVisibility);
        self::assertEquals($firstDocumentVisibility, $publicFolderVisibility);
        self::assertEquals($secondDocumentVisibility, $publicFolderVisibility);
    }

    public function provideTestCanNotToggleVisibiltyWithParentFolder(): ?\Generator
    {
        yield 'Should return 403 status code when authenticated as beneficiaire and folder has parent folder' => [
            BeneficiaryFixture::BENEFICIARY_MAIL,
        ];
        yield 'Should return 403 status code when authenticated as member with relay in common and folder has parent folder' => [
            MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
        ];
    }

    /** @dataProvider provideTestCanNotToggleVisibiltyWithParentFolder */
    public function testCanNotToggleVisibiltyWithParentFolder(string $userMail): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $folder = FolderFactory::findOrCreate(['beneficiaire' => $beneficiary, 'dossierParent' => FolderFactory::random()])->object();

        $this->assertRoute(sprintf(self::URL, $folder->getId()), 403, $userMail, null, 'PATCH', true);
    }
}
