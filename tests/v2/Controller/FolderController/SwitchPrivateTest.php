<?php

namespace App\Tests\v2\Controller\FolderController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class SwitchPrivateTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/folder/%s/toggle-visibility';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login', 'PATCH'];
        yield 'Should redirect to list when authenticated as beneficiaire' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary/%s/documents'];
        yield 'Should redirect to list when authenticated as member with relay in common' => [self::URL, 302, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, '/beneficiary/%s/documents'];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
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
        $expectedRedirect = $expectedRedirect ? sprintf($expectedRedirect, $beneficiary->getId()) : '';
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        // Also check that authorized Pro can't update private data
        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES === $userMail) {
            $privateFolder = FolderFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => true])->object();
            $newUrl = sprintf(self::URL, $privateFolder->getId());
            $this->assertRoute($newUrl, 403, $userMail, null, $method);
        }
    }

    public function provideTestCanNotSwitchPrivateWithParentFolder(): ?\Generator
    {
        yield 'Should return 403 status code when authenticated as beneficiaire and folder has private parent folder' => [
            BeneficiaryFixture::BENEFICIARY_MAIL,
            true,
            403,
        ];
        yield 'Should redirect when authenticated as beneficiaire and folder has shared parent folder' => [
            BeneficiaryFixture::BENEFICIARY_MAIL,
            false,
            302,
        ];
        yield 'Should return 403 status code when authenticated as member with relay in common and folder has private parent folder' => [
            MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
            true,
            403,
        ];
        yield 'Should redirect when authenticated as member with relay in common and folder has shared parent folder' => [
            MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
            false,
            302,
        ];
    }

    /** @dataProvider provideTestCanNotSwitchPrivateWithParentFolder */
    public function testCanNotSwitchPrivateWithPrivateParentFolder(string $userMail, bool $isPrivateParentFolder, int $statusCode): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $folder = FolderFactory::findOrCreate([
            'bPrive' => false,
            'beneficiaire' => $beneficiary,
            'dossierParent' => FolderFactory::random(['bPrive' => $isPrivateParentFolder]),
        ])->object();

        $this->assertRoute(sprintf(self::URL, $folder->getId()), $statusCode, $userMail);
    }
}
