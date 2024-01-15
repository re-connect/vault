<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Security\HelperV2\Oauth2Helper;
use App\ServiceV2\Traits\UserAwareTrait;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CheckAcceptedTermsOfUseSubscriber implements EventSubscriberInterface
{
    use UserAwareTrait;
    private const ALLOWED_ROUTES = ['user_first_visit', 'user_cgs', 'user_delete'];

    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly Oauth2Helper $oauth2Helper,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function checkUserAcceptedTermsOfUse(RequestEvent $event): void
    {
        $user = $this->getUser();
        $currentRoute = $event->getRequest()->get('_route');

        if (!$user instanceof User || $this->isOauth2User()) {
            return;
        }

        if (
            $user->mustAcceptTermsOfUse()
            && $event->isMainRequest()
            && !in_array($currentRoute, self::ALLOWED_ROUTES)
        ) {
            $event->setResponse(new RedirectResponse($this->router->generate('user_first_visit')));
        }
    }

    private function isOauth2User(): bool
    {
        $token = $this->tokenStorage->getToken();

        return $token instanceof OAuth2Token && $this->oauth2Helper->getClient($token);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'checkUserAcceptedTermsOfUse',
        ];
    }
}
