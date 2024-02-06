<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class CheckAcceptedTermsOfUseSubscriber extends AbstractWebUserSubscriber implements EventSubscriberInterface
{
    public function checkUserAcceptedTermsOfUse(RequestEvent $event): void
    {
        $user = $this->getUser();

        if (!$this->isAuthenticatedWebUser($user)) {
            return;
        }

        if (
            $user->mustAcceptTermsOfUse()
            && $event->isMainRequest()
            && !in_array($event->getRequest()->get('_route'), self::FIRST_VISIT_ROUTES)
        ) {
            $event->setResponse(new RedirectResponse($this->router->generate('user_first_visit')));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'checkUserAcceptedTermsOfUse',
        ];
    }
}
