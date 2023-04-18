<?php

namespace App\EventSubscriber\Logs;

use App\ServiceV2\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccessEvent',
        ];
    }

    public function onLoginSuccessEvent(LoginSuccessEvent $event): void
    {
        $this->logger->logLogin();
    }
}
