<?php

namespace App\Tests\v2\Controller\UserController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Repository\UserRepository;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class DeleteTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/user/delete';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated on delete page' => [self::URL, 302, null, '/login'];
        yield 'Should redirect authenticated as member' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/professional/beneficiaries'];
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
        $form = $crawler->selectButton('Oui')->form();
        $client->submit($form);

        // Is deleted
        self::assertResponseStatusCodeSame(302);
        self::assertResponseRedirects('/login');
        $user = $userRepository->find($user->getId());
        self::assertNull($user);
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
