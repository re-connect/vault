<?php

namespace App\Tests\v2\Controller\UserController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Repository\UserRepository;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\v2\BucketServiceSpyTrait;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class DeleteTest extends AbstractControllerTest implements TestRouteInterface
{
    use BucketServiceSpyTrait;

    private const URL = '/user/delete';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated on delete page' => [self::URL, 302, null, '/login'];
        yield 'Should not access delete when authenticated as member' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
        yield 'Should access delete when authenticated as beneficiary' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
    }

    public function testDeleteBeneficiaryAccount(): void
    {
        // Beneficiary log in and go to delete page
        self::ensureKernelShutdown();
        $client = static::createClient();
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $this->getTestUserFromDb(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_DELETE);
        $client->loginUser($user);
        $crawler = $client->request('GET', self::URL);

        // Submit form
        $form = $crawler->selectButton('Supprimer')->form();
        $client->submit($form);

        // Is deleted
        self::assertResponseStatusCodeSame(302);
        self::assertResponseRedirects('/login');
        $user = $userRepository->find($user->getId());
        self::assertNull($user);
    }

    public function testDeletingAccountRemovesDocumentFilesFromStorage(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $deletedKeys = $this->spyOnDeletedFiles($client);
        DocumentFactory::createOne([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_DELETE),
            'objectKey' => 'account-object-key',
            'thumbnailKey' => 'account-thumbnail-key',
        ]);
        $client->loginUser($this->getTestUserFromDb(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_DELETE));
        $crawler = $client->request('GET', self::URL);

        $client->submit($crawler->selectButton('Supprimer')->form());

        self::assertResponseRedirects('/login');
        self::assertContains('account-object-key', $deletedKeys->getArrayCopy());
        self::assertContains('account-thumbnail-key', $deletedKeys->getArrayCopy());
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
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }
}
