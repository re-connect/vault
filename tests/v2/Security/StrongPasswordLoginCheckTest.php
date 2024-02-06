<?php

namespace App\Tests\v2\Security;

use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

class StrongPasswordLoginCheckTest extends WebTestCase
{
    use Factories;
    /**
     * @dataProvider provideTestLoginSuccessHydrateUserDependingPasswordWeakness
     */
    public function testLoginSuccessHydrateUserDependingPasswordWeakness(string $email, string $password, bool $shouldFlagUserWithLatestPolicy): void
    {
        $client = $this->createClient();
        $user = UserFactory::findByEmail($email)->object();
        $user->setHasPasswordWithLatestPolicy(false);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        self::assertFalse($user->hasPasswordWithLatestPolicy());

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Connexion')->form();

        $form->setValues([
            '_username' => $user->getEmail(),
            '_password' => $password,
        ]);
        $client->submit($form);

        $user = UserFactory::find($user)->object();
        self::assertEquals($shouldFlagUserWithLatestPolicy, $user->hasPasswordWithLatestPolicy());
    }

    public function provideTestLoginSuccessHydrateUserDependingPasswordWeakness(): \Generator
    {
        yield 'Should flag user with latest policy' => [
            MemberFixture::MEMBER_MAIL,
            'StrongPassword1!',
            true,
        ];
        yield 'Should not flag user with latest policy' => [
            MemberFixture::MEMBER_PASSWORD_WEAK,
            'password',
            false,
        ];
    }
}
