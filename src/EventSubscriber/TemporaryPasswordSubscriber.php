<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * A beneficiary created from Reconnect Pro has a password known by the pro:
 * they must choose their own before anything else, terms of use included.
 * The weak password page stays allowed to avoid a redirect loop with PasswordPolicySubscriber.
 */
class TemporaryPasswordSubscriber extends AbstractWebUserSubscriber implements EventSubscriberInterface
{
    private const array ALLOWED_ROUTES = [
        'app_update_password',
        'improve_password',
        '2fa_login',
        'resend_auth_code',
        're_main_change_lang',
        're_admin_annexe',
    ];

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
