<?php

namespace App\Tests\v2\Service;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

class MailerServiceTest extends WebTestCase
{
    use Factories;

    /** @dataProvider provideShouldSendDuplicateUsernameAlert */
    public function testShouldSendDuplicateUsernameAlert(string $typeUser): void
    {
        // We create 2 users with duplicate firstname and lastname
        $user = UserFactory::createOne([
            'typeUser' => $typeUser,
        ])->object();
        $duplicatedUser = UserFactory::createOne([
            'prenom' => $user->getPrenom(),
            'nom' => $user->getNom(),
            'typeUser' => $typeUser,
        ])->object();

        $email = $this->getMailerMessage();
        if (User::USER_TYPE_BENEFICIAIRE === $typeUser) {
            $this->assertEmailTextBodyContains($email, sprintf(
                'Doublons de username bénéficiaire : %s',
                $duplicatedUser->getUsername())
            );
        } else {
            self::assertNull($email);
        }
    }

    /** @dataProvider provideShouldNotSendDuplicateUsernameAlert */
    public function testShouldNotSendDuplicateUsernameAlert(string $typeUser): void
    {
        // We create 2 users with different information
        UserFactory::createOne([
            'typeUser' => $typeUser,
        ])->object();
        UserFactory::createOne([
            'typeUser' => $typeUser,
        ])->object();

        $email = $this->getMailerMessage();
        self::assertNull($email);
    }

    public function provideShouldSendDuplicateUsernameAlert(): \Generator
    {
        yield 'Should be sent with beneficiary' => [User::USER_TYPE_BENEFICIAIRE];
        yield 'Should not be sent with member' => [User::USER_TYPE_MEMBRE];
    }

    public function provideShouldNotSendDuplicateUsernameAlert(): \Generator
    {
        yield 'Should not be sent with beneficiary' => [User::USER_TYPE_BENEFICIAIRE];
        yield 'Should not be sent with member' => [User::USER_TYPE_MEMBRE];
    }
}
