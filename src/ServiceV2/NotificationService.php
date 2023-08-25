<?php

namespace App\ServiceV2;

use App\Entity\Beneficiaire;
use App\Entity\Evenement;
use App\Entity\Rappel;
use App\Entity\SMS;
use App\Entity\User;
use App\Entity\UserCentre;
use App\FormV2\UserCreation\SecretQuestionType;
use App\Helper\SecretQuestionsHelper;
use App\HelperEntity\Notification;
use App\HelperEntity\NotificationAction;
use App\HelperEntity\NotificationForm;
use App\ManagerV2\RelayManager;
use App\ServiceV2\Traits\SessionsAwareTrait;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationService
{
    use SessionsAwareTrait;
    use UserAwareTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
        private readonly TexterInterface $texter,
        private readonly EntityManagerInterface $em,
        private readonly RouterInterface $router,
        private RequestStack $requestStack,
        private readonly Security $security,
        private readonly LoginLinkHandlerInterface $loginLinkHandler,
        private readonly SecretQuestionsHelper $secretQuestionsHelper,
        private readonly RelayManager $relayManager,
        private readonly FormFactoryInterface $formFactory,
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
        $beneficiary = $event->getBeneficiaire();
        $number = $beneficiary?->getUser()?->getTelephone();
        if (null === $number) {
            return;
        }

        try {
            $message = $this->getReminderMessage($event);
            $this->sendSms($number, $message);
            $reminder->setBEnvoye(true);
            $sms = SMS::createReminderSms($reminder, $event, $beneficiary, $number);

            $this->em->persist($sms);
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

    /** @return Notification[] */
    public function getUserNotifications(): array
    {
        return [
            ...$this->getMissingSecretQuestionNotification(),
            ...$this->getRelayInvitationNotifications(),
        ];
    }

    /** @return Notification[] */
    private function getRelayInvitationNotifications(): array
    {
        return array_map(
            [$this, 'createRelayInvitationNotification'],
            $this->relayManager->getPendingRelays($this->getUser())
        );
    }

    private function createRelayInvitationNotification(UserCentre $userCentre): Notification
    {
        $relay = $userCentre->getCentre();

        return new Notification(
            'user.pendingCentre.title',
            $relay->getNom(),
            'hotel',
            $relay->getAdresse()?->toHTML() ?? $this->translator->trans('relay_has_no_address'),
            [
                new NotificationAction('main.refuser', $this->router->generate('deny_relay', ['id' => $relay->getId()]), 'light'),
                new NotificationAction('accept', $this->router->generate('accept_relay', ['id' => $relay->getId()])),
            ],
        );
    }

    /** @return Notification[] */
    private function getMissingSecretQuestionNotification(): array
    {
        $notifications = [];
        $beneficiary = $this->secretQuestionsHelper->getCurrentBeneficiary();
        if ($this->secretQuestionsHelper->beneficiaryMissesSecretQuestion($beneficiary)) {
            $form = $this->formFactory->create(SecretQuestionType::class, $beneficiary, [
                'action' => $this->router->generate('set_secret_question', ['id' => $beneficiary->getId()]),
            ]);

            $notifications[] = new Notification(
                'missing_secret_question',
                '',
                null,
                'missing_secret_question_text',
                [],
                new NotificationForm($form->createView(), 'conditional-field'),
            );
        }

        return $notifications;
    }
}
