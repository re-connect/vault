<?php

namespace App\Twig\ExtensionV2;

use App\HelperEntity\Notification;
use App\Manager\SecretQuestionManager;
use App\ManagerV2\RelayManager;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    use UserAwareTrait;

    public function __construct(
        private readonly RelayManager $relayManager,
        private readonly SecretQuestionManager $secretQuestionManager,
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getRelayInvitationNotifications', [$this, 'getRelayInvitationNotifications'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
            new TwigFunction('getUserNotifications', [$this, 'getUserNotifications']),
        ];
    }

    public function getUserNotifications(): array
    {
        return $this->secretQuestionManager->currentBeneficiaryMissesSecretQuestion() ? [
            new Notification('missing_secret_question', 'missing_secret_question_text'),
        ] : [];
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function getRelayInvitationNotifications(Environment $env): ?string
    {
        $relay = $this->relayManager->getFirstPendingRelay($this->getUser());

        return !$relay ? '' : $env->render('v2/notifications/relay_invitation_notification.html.twig', [
            'relay' => $relay->getCentre(),
        ]);
    }
}
