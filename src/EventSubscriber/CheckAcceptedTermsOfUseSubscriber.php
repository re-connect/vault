<?php

namespace App\EventSubscriber;

use App\Domain\TermsOfUse\TermsOfUseHelper;
use App\Security\HelperV2\Oauth2Helper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CheckAcceptedTermsOfUseSubscriber extends AbstractWebUserSubscriber implements EventSubscriberInterface
{
    public function __construct(Security $security, RouterInterface $router, TokenStorageInterface $tokenStorage, Oauth2Helper $oauth2Helper, private readonly TermsOfUseHelper $termsOfUseHelper)
    {
        parent::__construct($security, $router, $tokenStorage, $oauth2Helper);
    }

    public function checkUserAcceptedTermsOfUse(RequestEvent $event): void
    {
        $user = $this->getUser();

        // A temporary password must be replaced first (TemporaryPasswordSubscriber), so that only the account owner accepts the terms.
        if (!$user || !$this->isAuthenticatedWebUser($user) || $user->hasTemporaryPassword()) {
            return;
        }

        if (
            $this->termsOfUseHelper->mustAcceptTermsOfUse($user)
            && $event->isMainRequest()
            && !in_array($event->getRequest()->attributes->get('_route'), self::FIRST_VISIT_ROUTES)
        ) {
            $event->setResponse(new RedirectResponse($user->isFirstVisit() ? $this->router->generate('user_first_visit') : $this->router->generate('user_cgs')));
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'checkUserAcceptedTermsOfUse',
        ];
    }
}
