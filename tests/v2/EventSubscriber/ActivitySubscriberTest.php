<?php

namespace App\Tests\v2\EventSubscriber;

use App\EventSubscriber\Logs\ActivitySubscriber;
use App\ServiceV2\ActivityLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class ActivitySubscriberTest extends TestCase
{
    public function testEventSubscription()
    {
        $this->assertArrayHasKey(LoginSuccessEvent::class, ActivitySubscriber::getSubscribedEvents());
    }

    public function testCallActivityLoggerOnLoginSuccess(): void
    {
        $activityLogger = $this->createMock(ActivityLogger::class);
        $subscriber = new ActivitySubscriber($activityLogger);
        $event = new LoginSuccessEvent($this->createMock(AuthenticatorInterface::class), $this->createMock(Passport::class), $this->createMock(TokenInterface::class), new Request(), null, '');

        $activityLogger->expects($this->once())->method('logLogin');

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($event);
    }
}
