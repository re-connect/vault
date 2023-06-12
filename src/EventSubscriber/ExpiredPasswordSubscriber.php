<?php

namespace App\EventSubscriber;

use App\ServiceV2\GdprService;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

class ExpiredPasswordSubscriber implements EventSubscriberInterface
{
    use UserAwareTrait;

    public function __construct(
        private readonly Security $security,
        private readonly GdprService $gdprService,
        private readonly RouterInterface $router,
    ) {
    }

    public function checkPasswordExpiration(RequestEvent $event): void
    {
        $user = $this->getUser();
        if (
            null !== $user
            && false === $user->isBeneficiaire()
            && $this->gdprService->isPasswordExpired()
            && $event->isMainRequest()
            && 'app_update_password' !== $event->getRequest()->get('_route')
        ) {
            $event->setResponse(new RedirectResponse($this->router->generate('app_update_password')));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'checkPasswordExpiration',
        ];
    }
}
