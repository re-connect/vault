<?php

namespace App\Tests\v2\Controller\FolderController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class TreeViewMoveTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/folder/%d/tree-view-move';

    /** @dataProvider provideTestRoute */
    public function testRoute(
        string $url,
        int $expectedStatusCode,
        string $userMail = null,
        string $expectedRedirect = null,
        string $method = 'GET',
        bool $isXmlHttpRequest = false,
        array $body = [],
    ): void {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $publicFolder = FolderFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => false])->object();

        $url = sprintf($url, $publicFolder->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        // Also check that authorized Pro can't update private data
        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES === $userMail) {
            $privateFolder = FolderFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => true])->object();
            $newUrl = sprintf(self::URL, $privateFolder->getId());
            $this->assertRoute($newUrl, 403, $userMail, null, $method, true);
        }
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code when authenticated as member with relay in common' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
    }

    public function provideTestTreeViewMove(): ?\Generator
    {
        yield 'Shared folder should be hydrated with parent visibility' => [false];
        yield 'Private folder should be hydrated with parent visibility' => [true];
    }

    /** @dataProvider provideTestTreeViewMove */
    public function testTreeViewMove(bool $isPrivate): void
    {
        $clientTest = static::createClient();
        $user = $this->getTestUserFromDb(BeneficiaryFixture::BENEFICIARY_MAIL);
        $clientTest->loginUser($user);
        $beneficiary = $user->getSubjectBeneficiaire();

        $folder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => $isPrivate])->object();

        // We access first folder link in the tree view, and access folderId
        $crawler = $clientTest->request('GET', sprintf(self::URL, $folder->getId()));
        $treeViewMoveUri = $crawler->filter('ul.tree-list > li > a')->attr('href');
        $uriToArray = explode('/', $treeViewMoveUri);
        $parentFolderId = end($uriToArray);
        $parentFolder = FolderFactory::find(['id' => $parentFolderId])->object();

        // We hydrate folder with desired visibility before moving document inside for test purposes
        $parentFolder->setBprive(!$isPrivate);

        $clientTest->request('GET', $treeViewMoveUri);
        $folder = FolderFactory::find($folder)->object();
        $parentFolder = FolderFactory::find($parentFolder)->object();
        self::assertSame($parentFolder, $folder->getDossierParent());
        self::assertEquals($parentFolder->getBprive(), $folder->getBprive());
    }
}
