<?php

namespace App\EventSubscriber;

use App\Helper\TermsOfUseHelper;
use App\Security\HelperV2\Oauth2Helper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CheckAcceptedTermsOfUseSubscriber extends AbstractWebUserSubscriber implements EventSubscriberInterface
{
    public const string CGS_FEATURE_FLAG_NAME = 'NEW_CGS';

    public function __construct(Security $security, RouterInterface $router, TokenStorageInterface $tokenStorage, Oauth2Helper $oauth2Helper, private readonly TermsOfUseHelper $termsOfUseHelper)
    {
        parent::__construct($security, $router, $tokenStorage, $oauth2Helper);
    }

    public function checkUserAcceptedTermsOfUse(RequestEvent $event): void
    {
        $user = $this->getUser();

        if (!$user || !$this->isAuthenticatedWebUser($user)) {
            return;
        }

        if (
            $this->termsOfUseHelper->mustAcceptTermsOfUse($user)
            && $event->isMainRequest()
            && !in_array($event->getRequest()->get('_route'), self::FIRST_VISIT_ROUTES)
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
