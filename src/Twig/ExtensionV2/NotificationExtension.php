<?php

namespace App\Twig\ExtensionV2;

use App\Entity\User;
use App\Repository\CentreRepository;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    use UserAwareTrait;

    public function __construct(
        private readonly CentreRepository $relayRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private Security $security,
        private RequestStack $requestStack,
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
        $user = $this->getUser();

        if ($user && $this->canReceiveNotifications($user)) {
            if ($relays = $this->relayRepository->findPersonalRelays($user, false)) {
                return $env->render('v2/notifications/relay_invitation_notification.html.twig', [
                    'pendingRelays' => $relays,
                    'user' => $user,
                ]);
            }
        }

        return null;
    }

    private function canReceiveNotifications(User $user): bool
    {
        return $user->isBeneficiaire() || $user->isMembre();
    }
}
