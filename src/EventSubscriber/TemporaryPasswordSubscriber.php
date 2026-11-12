<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * A beneficiary created from Reconnect Pro has a password known by the pro:
 * they must choose their own before accessing their vault (after the terms of use).
 */
class TemporaryPasswordSubscriber extends AbstractWebUserSubscriber implements EventSubscriberInterface
{
    private const array ALLOWED_ROUTES = [...self::FIRST_VISIT_ROUTES, 'app_update_password', 'improve_password'];

    public function redirectToPasswordUpdate(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->getUser();
        if (!$user || !$this->isAuthenticatedWebUser($user) || !$user->hasTemporaryPassword()) {
            return;
        }

        if (!in_array($event->getRequest()->attributes->get('_route'), self::ALLOWED_ROUTES, true)) {
            $event->setResponse(new RedirectResponse($this->router->generate('app_update_password')));
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'redirectToPasswordUpdate',
        ];
    }
}
