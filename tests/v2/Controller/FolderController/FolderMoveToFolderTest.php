<?php

namespace App\Tests\v2\Controller\FolderController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class FolderMoveToFolderTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/folders/%s/move-to-folder/%s';

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $parentFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary])->object();
        $subFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary])->object();

        $url = sprintf(
            $url,
            $subFolder->getId(),
            $parentFolder->getId(),
        );
        $expectedRedirect = $expectedRedirect ? sprintf($expectedRedirect, $beneficiary->getId()) : '';
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        FolderFactory::find(['id' => $subFolder->getId()])->remove();
        FolderFactory::find(['id' => $parentFolder->getId()])->remove();
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
        $parentFolder = FolderFactory::createOne(['beneficiaire' => $testedBeneficiary, 'bPrive' => true])->object();
        $subFolder = FolderFactory::createOne(['beneficiaire' => $testedBeneficiary, 'bPrive' => false])->object();
        $randomFolder = FolderFactory::createOne(['beneficiaire' => $randomBeneficiary])->object();

        // Tested beneficiary tries to move folder inside random beneficiarie's folder
        $clientTest->request('GET', sprintf(self::URL, $subFolder->getId(), $randomFolder->getId()));
        self::assertResponseStatusCodeSame(403);

        // Then move in a folder that belongs to him
        $clientTest->request('GET', sprintf(self::URL, $subFolder->getId(), $parentFolder->getId()));
        $parentFolder = FolderFactory::find(['id' => $parentFolder->getId()]);
        $subFolder = FolderFactory::find(['id' => $subFolder->getId()]);
        self::assertEquals($parentFolder->object()->getSousDossiers()->last()->getId(), $subFolder->object()->getId());
        self::assertEquals($parentFolder->object()->getSousDossiers()->last()->getBprive(), $subFolder->object()->getBprive());

        $subFolder->remove();
        $parentFolder->remove();
    }
}
