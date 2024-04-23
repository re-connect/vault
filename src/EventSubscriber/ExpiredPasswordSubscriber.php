<?php

namespace App\EventSubscriber;

use App\ServiceV2\GdprService;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

#[AsEventListener(RequestEvent::class, 'checkPasswordExpiration')]
class ExpiredPasswordSubscriber
{
    use UserAwareTrait;

    private const array ALLOWED_ROUTES = ['app_update_password', 'improve_password', '2fa_login'];

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
            && !in_array($event->getRequest()->get('_route'), self::ALLOWED_ROUTES)
        ) {
            $event->setResponse(new RedirectResponse($this->router->generate('app_update_password')));
        }
    }
}
