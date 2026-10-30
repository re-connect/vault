<?php

namespace App\Tests\v2\Service;

use App\Entity\User;
use App\ServiceV2\Mailer\MailerService;
use App\Tests\Factory\UserFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
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
        ]);
        $duplicatedUser = UserFactory::createOne([
            'prenom' => $user->getPrenom(),
            'nom' => $user->getNom(),
            'typeUser' => $typeUser,
        ]);

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
        ]);
        UserFactory::createOne([
            'typeUser' => $typeUser,
        ]);

        $email = $this->getMailerMessage();
        self::assertNull($email);
    }

    public function provideShouldSendDuplicateUsernameAlert(): \Generator
    {
        yield 'Should be sent with beneficiary' => [User::USER_TYPE_BENEFICIAIRE];
        yield 'Should not be sent with member' => [User::USER_TYPE_MEMBRE];
        yield 'Should not be sent with gestionnaire' => [User::USER_TYPE_GESTIONNAIRE];
    }

    public function provideShouldNotSendDuplicateUsernameAlert(): \Generator
    {
        yield 'Should not be sent with beneficiary' => [User::USER_TYPE_BENEFICIAIRE];
        yield 'Should not be sent with member' => [User::USER_TYPE_MEMBRE];
        yield 'Should not be sent with gestionnaire' => [User::USER_TYPE_GESTIONNAIRE];
    }

    /** @dataProvider providePersonalDataRequestEmailMentionsPreprod */
    public function testPersonalDataRequestEmailMentionsPreprod(string $env, bool $shouldMentionPreprod): void
    {
        $sentEmail = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')->willReturnCallback(function (Email $email) use (&$sentEmail): void {
            $sentEmail = $email;
        });

        $mailerService = new MailerService(
            $mailer,
            $this->createStub(RouterInterface::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(TranslatorInterface::class),
            $this->createStub(Security::class),
            'sender@reconnect.fr',
            ['admin@reconnect.fr'],
            'duplicate@reconnect.fr',
            $env,
        );

        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn(42);
        $mailerService->sendPersonalDataRequestEmail($user);

        self::assertInstanceOf(Email::class, $sentEmail);
        self::assertStringContainsString('Un utilisateur (id user = 42)', $sentEmail->getTextBody());
        self::assertSame($shouldMentionPreprod, str_contains($sentEmail->getTextBody(), 'environnement de préproduction'));
    }

    public function providePersonalDataRequestEmailMentionsPreprod(): \Generator
    {
        yield 'Should mention preprod in preprod' => ['preprod', true];
        yield 'Should not mention preprod in prod' => ['prod', false];
    }
}
