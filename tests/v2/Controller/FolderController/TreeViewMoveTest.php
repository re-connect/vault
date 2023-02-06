<?php

namespace App\Tests\v2\Controller\FolderController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class TreeViewMoveTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/folder/%d/tree-view-move';

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        $document = FolderFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object()->getId(),
        ])->object();
        $url = sprintf($url, $document->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
    }

    public function testTreeViewMove(): void
    {
        $clientTest = static::createClient();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $clientTest->loginUser($beneficiary->getUser());
        FolderFactory::createOne(['beneficiaire' => $beneficiary])->object();
        $secondFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary])->object();

        // We access first folder link in the tree view, and access folderId
        $crawler = $clientTest->request('GET', sprintf(self::URL, $secondFolder->getId()));
        $treeViewMoveUri = $crawler->filter('ul.tree-list > li > a')->eq(2)->attr('href');
        $uriToArray = explode('/', $treeViewMoveUri);
        $parentFolderId = end($uriToArray);

        $clientTest->request('GET', $treeViewMoveUri);
        $secondFolder = FolderFactory::find(['id' => $secondFolder->getId()])->object();
        $parentFolder = FolderFactory::find(['id' => $parentFolderId])->object();
        self::assertSame($secondFolder->getDossierParent(), $parentFolder);
        $secondFolder->setDossierParent();
    }
}
