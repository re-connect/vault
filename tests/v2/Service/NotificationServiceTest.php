<?php

namespace App\Tests\v2\Service;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Beneficiaire;
use App\Entity\Evenement;
use App\Entity\Rappel;
use App\ServiceV2\NotificationService;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\AuthenticatedTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Test\Factories;

class NotificationServiceTest extends AuthenticatedTestCase
{
    use Factories;
    private Beneficiaire $beneficiaryUser;
    private EntityManagerInterface $em;
    private NotificationService $notificationService;
    private MockObject|TranslatorInterface $translatorMock;
    private TranslatorInterface $translator;
    private MockObject|LoggerInterface $loggerMock;
    private MockObject|TexterInterface $texterMock;
    private string $IOSAppLink = 'https://apple.co/2wYQdF8';
    private string $androidAppLink = 'https://bit.ly/2OtQuoZ';

    protected function setUp(): void
    {
        parent::setUp();
        $this->beneficiaryUser = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $this->em = $this->getEntityManager();
        $this->translatorMock = $this->createMock(TranslatorInterface::class);
        $this->translator = $this->getContainer()->get(TranslatorInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $requestStackMock = $this->createMock(RequestStack::class);
        $loginLinkHandlerMock = $this->createMock(LoginLinkHandlerInterface::class);
        $this->texterMock = $this->createMock(TexterInterface::class);

        $this->notificationService = new NotificationService(
            $this->translatorMock,
            $this->loggerMock,
            $this->texterMock,
            $this->em,
            $requestStackMock,
            $loginLinkHandlerMock,
            $this->IOSAppLink,
            $this->androidAppLink,
        );
    }

    public function testSendSmsResetPasswordSuccessfulLog(): void
    {
        $smsCode = '12345';
        $phoneNumber = '+33666666666';
        $message = 'Le code pour réinitialiser votre mot de passe reconnect est 12345';

        $this->translatorMock->expects($this->once())->method('trans')->willReturn($message);
        $this->loggerMock->expects($this->once())->method('info')->with(
            sprintf('SMS envoyé à %s : %s', $phoneNumber, $message)
        );

        $this->notificationService->sendSmsResetPassword($smsCode, $phoneNumber);
    }

    public function testSendSmsResetPasswordErrorLog(): void
    {
        $smsCode = '12345';
        $phoneNumber = $this->beneficiaryUser->getUser()->getTelephone();
        $infoMessage = 'Le code pour réinitialiser votre mot de passe reconnect est 12345';
        $errorLogMessage = 'L\'envoi du SMS a échoué, veuillez vérifier que le numéro de téléphone est valide';
        $exceptionMessage = 'Fake exception message';

        $this->texterMock->method('send')->will($this->throwException(new \Exception($exceptionMessage)));
        $this->translatorMock->expects($this->exactly(2))->method('trans')->willReturnOnConsecutiveCalls(
            $infoMessage,
            $errorLogMessage
        );

        $this->loggerMock->expects($this->exactly(2))->method('error')->withConsecutive(
            [
                sprintf(
                    '%s. Cause: %s',
                    $errorLogMessage,
                    $exceptionMessage
                ),
            ],
            [
                sprintf(
                    'Error sending sms to %s, content %s, cause %s ',
                    $phoneNumber,
                    $infoMessage,
                    $exceptionMessage,
                ),
            ]
        );

        $this->notificationService->sendSmsResetPassword($smsCode, $phoneNumber);
    }

    public function testSendSmsReminderSuccessfully(): void
    {
        // Create event and reminder
        $event = (new Evenement($this->beneficiaryUser))
            ->setNom('RDV Reconnect')
            ->setDate((new \DateTime())->modify('+1 day'));

        $reminder = (new Rappel())
            ->setDate((new \DateTime())->modify('+6 hours'))
            ->setEvenement($event);

        $this->em->persist($event);
        $this->em->flush();

        // reminder isn't sent
        self::assertFalse($reminder->getBEnvoye());

        // test successful log message
        $message = sprintf(
            '"Bonjour \nRappel RDV : %s \n%s"',
            $event->getNom(),
            $event->getDate()->format("d/m/Y à H\hi")
        );
        $this->translatorMock->expects($this->once())->method('trans')->willReturn($message);
        $this->loggerMock->expects($this->once())->method('info')->with(
            sprintf('SMS envoyé à %s : %s',
                $this->beneficiaryUser->getUser()->getTelephone(),
                $message
            )
        );

        $this->notificationService->sendSmsReminder($reminder);

        // reminder is sent
        self::assertTrue($reminder->getBEnvoye());
        $this->em->remove($event);
        $this->em->flush();
    }

    public function testSendSmsReminderErrorLog(): void
    {
        // Create event and reminder
        $event = (new Evenement($this->beneficiaryUser))
            ->setNom('RDV Reconnect')
            ->setDate((new \DateTime())->modify('+1 day'));

        $reminder = (new Rappel())
            ->setDate((new \DateTime())->modify('+6 hours'))
            ->setEvenement($event);

        $this->em->persist($event);
        $this->em->flush();

        // test error log message
        $reminderMessage = sprintf(
            '"Bonjour \nRappel RDV : %s \n%s"',
            $event->getNom(),
            $event->getDate()->format("d/m/Y à H\hi")
        );
        $errorLogMessage = 'L\'envoi du SMS a échoué, veuillez vérifier que le numéro de téléphone est valide';
        $exceptionMessage = 'Fake exception message';

        // We simulate exception
        $this->texterMock->method('send')->will($this->throwException(new \Exception($exceptionMessage)));
        $this->translatorMock->expects($this->exactly(2))->method('trans')->willReturnOnConsecutiveCalls(
            $reminderMessage,
            $errorLogMessage,
        );
        $this->loggerMock->expects($this->exactly(2))->method('error')->withConsecutive(
            [
                sprintf(
                    '%s. Cause: %s',
                    $errorLogMessage,
                    $exceptionMessage
                ),
            ],
            [
                sprintf(
                    'Error sending sms to %s, content %s, cause %s ',
                    $this->beneficiaryUser->getUser()->getTelephone(),
                    $reminderMessage,
                    $exceptionMessage,
                ),
            ]
        );

        $this->notificationService->sendSmsReminder($reminder);

        // reminder is not sent
        self::assertFalse($reminder->getBEnvoye());
        $this->em->remove($reminder);
        $this->em->remove($event);
        $this->em->flush();
    }

    public function testDoSendSmsErrorLog(): void
    {
        $smsCode = '12345';
        $phoneNumber = $this->beneficiaryUser->getUser()->getTelephone();
        $errorLogMessage = 'L\'envoi du SMS a échoué, veuillez vérifier que le numéro de téléphone est valide';
        $exceptionMessage = 'Fake exception message';

        // We simulate exception
        $this->texterMock->method('send')->will($this->throwException(new \Exception($exceptionMessage)));

        $this->translatorMock->expects($this->once())->method('trans')->willReturn($errorLogMessage);
        $this->loggerMock->expects($this->once())->method('error')->with(
            sprintf(
                '%s. Cause: %s',
                $errorLogMessage,
                $exceptionMessage
            ),
        );

        try {
            $this->notificationService->sendSms($smsCode, $phoneNumber);
        } catch (\Exception $e) {
        }
    }

    public function testSendFirstLoginSMSSuccessful(): void
    {
        $user = $this->beneficiaryUser->getUser();
        $password = 'F4kePassword';
        $message = $this->translator->trans('beneficiary_creation_remotely_sms', [
            '%pro.firstname%' => $user->getCreatorUser()->getEntity()->getPrenom(),
            '%pro.lastname%' => $user->getCreatorUser()->getEntity()->getNom(),
            '%center%' => $user->getCreatorCentre()->getEntity()->getNom(),
            '%username%' => $user->getUsername(),
            '%password%' => $password,
            '%IOSAppLink%' => $this->IOSAppLink,
            '%androidAppLink%' => $this->androidAppLink,
        ]);

        $this->translatorMock->expects($this->once())->method('trans')->willReturn($message);
        $this->notificationService->sendFirstLoginSMS($this->beneficiaryUser, $password);
    }
}
