<?php

namespace App\Listener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SessionIdleListener
{
    protected TokenStorageInterface $securityContext;
    protected RouterInterface $router;
    protected int $maxIdleTime;
    private RequestStack $requestStack;

    public function __construct(
        RequestStack $requestStack,
        TokenStorageInterface $securityContext,
        RouterInterface $router,
        int $maxIdleTime = 0
    ) {
        $this->securityContext = $securityContext;
        $this->router = $router;
        $this->maxIdleTime = $maxIdleTime;
        $this->requestStack = $requestStack;
    }

    public function onKernelRequest($event)
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return;
        }

        $session = $this->requestStack->getSession();

        if ($this->maxIdleTime > 0) {
            $session->start();
            $lapse = time() - $session->getMetadataBag()->getLastUsed();
            if ($lapse > $this->maxIdleTime) {
                $this->securityContext->setToken(null);
                $session->invalidate();
                $session->getFlashBag()->set('success', 'connexionForm.inactif');

                // Change the route if you are not using FOSUserBundle.
                $event->setResponse(new RedirectResponse($this->router->generate('app_logout')));
            }
        }
    }
}
