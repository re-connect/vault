<?php

namespace App\Twig\ExtensionV2;

use App\Entity\UserCentre;
use App\HelperEntity\Notification;
use App\HelperEntity\NotificationAction;
use App\Manager\SecretQuestionManager;
use App\ManagerV2\RelayManager;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    use UserAwareTrait;

    public function __construct(
        private readonly RelayManager $relayManager,
        private readonly SecretQuestionManager $secretQuestionManager,
        private readonly RouterInterface $router,
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('getUserNotifications', [$this, 'getUserNotifications'])];
    }

    public function getUserNotifications(): array
    {
        return [
            ...$this->getMissingSecretQuestionNotification(),
            ...$this->getRelayInvitationNotifications(),
        ];
    }

    public function getRelayInvitationNotifications(): array
    {
        return array_map([$this, 'createRelayInvitationNotification'], $this->relayManager->getPendingRelays($this->getUser()));
    }

    public function createRelayInvitationNotification(UserCentre $userCentre): Notification
    {
        $relay = $userCentre->getCentre();

        return new Notification(
            'user.pendingCentre.title',
            $relay->getNom(),
            $relay->getAdresse()->toHTML(),
            [
                new NotificationAction('main.refuser', $this->router->generate('deny_relay', ['id' => $relay->getId()]), 'light'),
                new NotificationAction('accept', $this->router->generate('accept_relay', ['id' => $relay->getId()]), 'green'),
            ],
        );
    }

    public function getMissingSecretQuestionNotification(): array
    {
        return $this->secretQuestionManager->currentBeneficiaryMissesSecretQuestion() ? [
            new Notification('missing_secret_question', 'missing_secret_question_text'),
        ] : [];
    }
}
