<?php

namespace App\EventSubscriber\Api;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RosalieBridgeSubscriber implements EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): RequestEvent
    {
        $request = $event->getRequest();
        $token = $request->query->get('access_token');
        if ($token) {
            $request->headers->set('Authorization', 'Bearer '.$token);
        }

        return $event;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1000],
        ];
    }
}
