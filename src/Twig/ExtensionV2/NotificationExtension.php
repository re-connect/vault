<?php

namespace App\Twig\ExtensionV2;

use App\ManagerV2\RelayManager;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    use UserAwareTrait;

    public function __construct(
        private readonly RelayManager $relayManager,
        private Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getRelayInvitationNotifications', [$this, 'getRelayInvitationNotifications'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
        ];
    }

    public function getRelayInvitationNotifications(Environment $env): ?string
    {
        return $env->render('v2/notifications/relay_invitation_notification.html.twig', [
            'relay' => $this->relayManager->getFirstPendingRelay($this->getUser()),
        ]);
    }
}
