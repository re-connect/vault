<?php

namespace App\EventSubscriber;

use App\ServiceV2\Traits\SessionsAwareTrait;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SessionTimeoutSubscriber implements EventSubscriberInterface
{
    use UserAwareTrait;
    use SessionsAwareTrait;

    public function __construct(private RequestStack $requestStack, private Security $security, private UrlGeneratorInterface $urlGenerator, private int $maxIdleTime)
    {
    }

    public function checkIdleTime(RequestEvent $event): void
    {
        $session = $this->getSession();

        if (!$this->getUser() || !$session instanceof Session) {
            return;
        }

        if (!$session->isStarted()) {
            $session->start();
        }

        $now = time();
        $lastActivity = (int) $session->get('last_activity', $now);

        if (($now - $lastActivity) > $this->maxIdleTime) {
            $session->invalidate();
            $this->security->logout(false);
            $this->addFlashMessage('error', 'session_timeout');
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('home')));

            return;
        }

        if ($event->isMainRequest()) {
            $session->set('last_activity', $now);
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'checkIdleTime',
        ];
    }
}
