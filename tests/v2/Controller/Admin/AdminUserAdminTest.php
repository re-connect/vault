<?php

namespace App\Tests\v2\Controller\Admin;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminUserAdminTest extends AbstractControllerTest
{
    private function createUserByRole(string $role): User
    {
        /** @var User $user */
        $user = UserFactory::createOne()->object();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, UserFactory::WEAK_PASSWORD_CLEAR));
        $user->setRoles([$role]);
        $user->setTypeUser($role);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * @dataProvider provideTestAdminLogin
     */
    public function testAdminLogin(string $role, string $expectedRedirection, bool $isAdmin): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->followRedirects();

        $user = $this->createUserByRole($role);

        $client->request('GET', '/');
        $client->submitForm('Connexion', [
            '_username' => $user->getEmail(),
            '_password' => UserFactory::WEAK_PASSWORD_CLEAR,
        ]);

        $this->assertStringContainsString($expectedRedirection, $client->getRequest()->getUri());

        if ($isAdmin) {
            $this->assertResponseIsSuccessful();
        } else {
            $this->assertContains($client->getResponse()->getStatusCode(), [200, 302, 403]);
        }
    }

    public function provideTestAdminLogin(): \Generator
    {
        yield 'Should login admin user to admin dashboard' => [User::USER_TYPE_ADMINISTRATEUR, '/admin/dashboard', true];
        yield 'Should login super admin user to admin dashboard' => [User::USER_TYPE_SUPER_ADMIN, '/admin/dashboard', true];
        yield 'Should not login beneficiary user to admin dashboard' => [User::USER_TYPE_BENEFICIAIRE, '/beneficiary', false];
        yield 'Should not login member user to admin dashboard' => [User::USER_TYPE_MEMBRE, '/beneficiary/affiliate', false];
    }

    /**
     * @dataProvider provideTestAccessCreateAdminPage
     */
    public function testCanAccessCreateAdminPage(string $email, bool $canAccess): void
    {
        $client = $this->assertRoute('/admin/dashboard', 200, $email);
        $client->request('GET', '/admin/app/user/create');

        if ($canAccess) {
            $this->assertResponseIsSuccessful();
        } else {
            $this->assertEquals(403, $client->getResponse()->getStatusCode());
        }
    }

    public function provideTestAccessCreateAdminPage(): \Generator
    {
        yield 'Should access admin create page with super admin user' => ['super_admin@mail.com', true];
        yield 'Should not access admin create page with admin user' => ['admin@mail.com', false];
    }

    /**
     * @dataProvider provideTestCreateAdmin
     */
    public function test2faIsEnabledAfterAdminCreated(string $role): void
    {
        $client = $this->assertRoute('/admin/dashboard', 200, 'super_admin@mail.com');
        $crawler = $client->request('GET', '/admin/app/user/create');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer')->form();

        $emailField = $this->findField($crawler, '[email]');
        $form[$emailField] = 'new.admin@reconnect.fr';

        $lastnameFiel = $this->findField($crawler, '[nom]');
        $form[$lastnameFiel] = 'Admin';

        $fistnameField = $this->findField($crawler, '[prenom]');
        $form[$fistnameField] = 'New';

        $typeUserField = $this->findField($crawler, '[typeUser]', 'select');
        $form[$typeUserField] = $role;

        $client->submit($form);

        $client->followRedirect();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)
            ->findOneBy(['email' => 'new.admin@reconnect.fr']);
        self::assertNotNull($user);
        self::assertTrue($user->isMfaEnabled());

        $this->assertStringContainsString(
            sprintf('/admin/app/user/%d/edit', $user->getId()),
            $client->getRequest()->getUri()
        );
    }

    public function provideTestCreateAdmin(): \Generator
    {
        yield 'Should activate 2fa after super admin user is created' => [User::USER_TYPE_SUPER_ADMIN];
        yield 'Should activate 2fa after admin user is created' => [User::USER_TYPE_ADMINISTRATEUR];
    }

    private function findField(Crawler $crawler, string $fieldName, ?string $fieldType = 'input'): string
    {
        return $crawler->filterXPath(
            sprintf('//%s[contains(@name, "%s")]', $fieldType, $fieldName)
        )->attr('name');
    }
}
