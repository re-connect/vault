<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class PasswordPolicySubscriber extends AbstractWebUserSubscriber implements EventSubscriberInterface
{
    private const array ALLOWED_ROUTES = [...self::FIRST_VISIT_ROUTES, 'improve_password'];

    public function checkUserHasUpdatePasswordWithLatestPolicy(RequestEvent $event): void
    {
        $user = $this->getUser();

        if (!$user || !$this->isAuthenticatedWebUser($user)) {
            return;
        }

        if (
            !$user->hasPasswordWithLatestPolicy()
            && $event->isMainRequest()
            && !in_array($event->getRequest()->get('_route'), self::ALLOWED_ROUTES)
        ) {
            $event->setResponse(new RedirectResponse($this->router->generate('improve_password')));
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'checkUserHasUpdatePasswordWithLatestPolicy',
        ];
    }
}
