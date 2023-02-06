<?php

namespace App\ServiceV2;

use App\Entity\Beneficiaire;
use App\Entity\Evenement;
use App\Entity\Rappel;
use App\Entity\User;
use App\ServiceV2\Traits\SessionsAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationService
{
    use SessionsAwareTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
        private readonly TexterInterface $texter,
        private readonly EntityManagerInterface $em,
        private RequestStack $requestStack,
        private readonly LoginLinkHandlerInterface $loginLinkHandler,
        private readonly string $iOSAppLink,
        private readonly string $androidAppLink,
    ) {
    }

    public function sendSmsResetPassword(string $code, string $number): void
    {
        $message = $this->translator->trans('user.reinitialiserMdp.smsMessage', ['%code%' => $code]);
        try {
            $this->sendSms($number, $message);
            $this->logger->info(sprintf('SMS envoyé à %s : %s', $number, $message));
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error sending sms to %s, content %s, cause %s ', $number, $message, $e->getMessage()));
        }
    }

    public function sendSmsReminder(Rappel $reminder): void
    {
        $event = $reminder->getEvenement();
        $number = $event->getBeneficiaire()?->getUser()?->getTelephone();
        if (null === $number) {
            return;
        }

        try {
            $message = $this->getReminderMessage($event);
            $this->sendSms($number, $message);
            $reminder->setBEnvoye(true);
            $this->em->flush();
            $this->logger->info(sprintf('SMS envoyé à %s : %s', $number, $message));
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error sending sms to %s, content %s, cause %s ', $number, $message, $e->getMessage()));
        }
    }

    /**
     * @throws \Exception
     */
    public function sendSms(string $number, string $message): void
    {
        $sms = (new SmsMessage($number, $message));
        if (str_contains($number, '+1')) {
            $sms->transport('vonageUS');
        }

        try {
            $this->texter->send($sms);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('%s. Cause: %s', $this->translator->trans('sms_sent_failed'), $e->getMessage()));
            $this->addFlashMessage('danger', 'sms_sent_failed');

            throw $e;
        }
    }

    private function getReminderMessage(Evenement $event): string
    {
        $user = $event->getBeneficiaire()?->getUser();

        $message = $this->translator->trans('reminder_sms_content', [
            '%name%' => $event->getNom(),
            '%date%' => $event->getDate()->format("d/m/Y à H\hi"), ], 'messages', $user?->getLastLang() ?? 'fr');

        if (!empty($event->getLieu())) {
            $message .= PHP_EOL.$event->getLieu();
        }
        if (!empty($event->getCommentaire())) {
            $message .= PHP_EOL.$event->getCommentaire();
        }

        return $message;
    }

    public function sendFirstLoginSMS(Beneficiaire $beneficiary, string $password): void
    {
        if (!$number = $beneficiary->getUser()->getTelephone()) {
            return;
        }
        $loginLink = $this->loginLinkHandler->createLoginLink($beneficiary->getUser());
        $message = $this->getFirstLoginMessage($beneficiary, $loginLink->getUrl(), $password);

        try {
            $this->sendSms($number, $message);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('%s. Cause: %s', $this->translator->trans('sms_sent_failed'), $e->getMessage()));
        }
    }

    private function getFirstLoginMessage(Beneficiaire $beneficiary, string $loginLink, string $password): string
    {
        $user = $beneficiary->getUser();
        $creatorUser = $user->getCreatorUser()?->getEntity();
        $creatorRelay = $user->getCreatorCentre()?->getEntity();

        return $this->translator->trans('beneficiary_creation_remotely_sms', [
            '%pro.firstname%' => $creatorUser?->getPrenom(),
            '%pro.lastname%' => $creatorUser?->getNom(),
            '%center%' => $creatorRelay?->getNom(),
            '%username%' => $user->getUsername(),
            '%password%' => $password,
            '%autoLoginLink%' => $loginLink,
            '%IOSAppLink%' => $this->iOSAppLink,
            '%androidAppLink%' => $this->androidAppLink,
        ]);
    }

    public function sendVaultCreatedSms(User $user): void
    {
        if (!$telephone = $user->getTelephone()) {
            return;
        }

        try {
            $content = $this->translator->trans('beneficiary_creation_client_sms', [
                '%username%' => $user->getUsername(),
                '%autoLoginLink%' => $this->loginLinkHandler->createLoginLink($user)->getUrl(),
                '%IOSAppLink%' => $this->iOSAppLink,
                '%androidAppLink%' => $this->androidAppLink,
            ]);
            $this->sendSms($telephone, $content);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('%s. Cause: %s', $this->translator->trans('sms_sent_failed'), $e->getMessage()));
        }
    }
}
