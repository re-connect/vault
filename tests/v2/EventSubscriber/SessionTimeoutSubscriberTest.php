<?php

namespace App\Tests\v2\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\SessionTimeoutSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SessionTimeoutSubscriberTest extends TestCase
{
    private RequestStack $requestStack;
    private Security $security;
    private UrlGeneratorInterface $urlGenerator;
    private SessionTimeoutSubscriber $subscriber;
    private Session $session;
    private int $maxIdleTime = 1800; // 30 minutes

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->security = $this->createMock(Security::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->session = new Session(new MockArraySessionStorage());

        $this->subscriber = new SessionTimeoutSubscriber(
            $this->requestStack,
            $this->security,
            $this->urlGenerator,
            $this->maxIdleTime
        );
    }

    public function testUserIsLoggedOutWhenSessionTimesOut(): void
    {
        // Arrange
        $request = $this->setUpRequestWithMaxIddleTimePassed();
        // Expect logout to be called
        $this->security->expects($this->once())
            ->method('logout')
            ->with(false);

        $event = $this->createRequestEvent($request);

        // Act
        $this->subscriber->checkIdleTime($event);
    }

    public function testRedirectResponseToHomeIsSetWhenSessionTimesOut(): void
    {
        // Arrange
        $request = $this->setUpRequestWithMaxIddleTimePassed();
        $event = $this->createRequestEvent($request);

        // Act
        $this->subscriber->checkIdleTime($event);

        // Assert
        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response, 'Response should be a redirect');
        $this->assertEquals('/home', $response->getTargetUrl(), 'Should redirect to home');
    }

    public function testLastActivityTimestampIsUpdatedForMainRequests(): void
    {
        // Arrange
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        $this->session->start();
        $initialTime = time() - 100; // 100 seconds ago
        $this->session->set('last_activity', $initialTime);

        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack->push($request);

        $event = $this->createRequestEvent($request, true); // Main request

        // Act
        $this->subscriber->checkIdleTime($event);

        // Assert
        $lastActivity = $this->session->get('last_activity');
        $this->assertGreaterThan($initialTime, $lastActivity, 'Last activity should be updated to current time');
        $this->assertEqualsWithDelta(time(), $lastActivity, 2, 'Last activity should be approximately now');
    }

    public function testLastActivityTimestampIsNotUpdatedForSubRequests(): void
    {
        // Arrange
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        $this->session->start();
        $initialTime = time() - 100;
        $this->session->set('last_activity', $initialTime);

        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack->push($request);

        $event = $this->createRequestEvent($request, false); // Sub request

        // Act
        $this->subscriber->checkIdleTime($event);

        // Assert
        $lastActivity = $this->session->get('last_activity');
        $this->assertEquals($initialTime, $lastActivity, 'Last activity should not be updated for sub requests');
    }

    public function testNothingHappensIfThereIsNoUser(): void
    {
        // Arrange
        $this->security->method('getUser')->willReturn(null);

        $this->session->start();
        $this->session->set('last_activity', time() - $this->maxIdleTime - 100);

        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack->push($request);

        $this->security->expects($this->never())->method('logout');
        $this->urlGenerator->expects($this->never())->method('generate');

        $event = $this->createRequestEvent($request);

        // Act
        $this->subscriber->checkIdleTime($event);

        // Assert
        $this->assertTrue($this->session->isStarted(), 'Session should remain started');
        $this->assertNull($event->getResponse(), 'No response should be set');
    }

    public function testNothingHappensIfThereIsNoSession(): void
    {
        // Arrange
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        // Don't push a request with session to the stack
        $request = new Request();
        $this->requestStack->push($request);

        $this->security->expects($this->never())->method('logout');
        $this->urlGenerator->expects($this->never())->method('generate');

        $event = $this->createRequestEvent($request);

        // Act
        $this->subscriber->checkIdleTime($event);

        // Assert
        $this->assertNull($event->getResponse(), 'No response should be set');
    }

    public function testSessionStartsIfNotStarted(): void
    {
        // Arrange
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        // Session not started initially
        $this->assertFalse($this->session->isStarted());

        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack->push($request);

        $event = $this->createRequestEvent($request);

        // Act
        $this->subscriber->checkIdleTime($event);

        // Assert
        $this->assertTrue($this->session->isStarted(), 'Session should be started if not already started');
    }

    public function testSessionIsNotTimedOutWhenWithinIdleTime(): void
    {
        // Arrange
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        $this->session->start();
        $recentTime = time() - 100; // Only 100 seconds ago, well within max idle time
        $this->session->set('last_activity', $recentTime);

        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack->push($request);

        $this->security->expects($this->never())->method('logout');

        $event = $this->createRequestEvent($request);

        // Act
        $this->subscriber->checkIdleTime($event);

        // Assert
        $this->assertTrue($this->session->isStarted(), 'Session should remain started');
        $this->assertNull($event->getResponse(), 'No redirect response should be set');
    }

    public function testGetSubscribedEvents(): void
    {
        // Act
        $events = SessionTimeoutSubscriber::getSubscribedEvents();

        // Assert
        $this->assertArrayHasKey(RequestEvent::class, $events);
        $this->assertEquals('checkIdleTime', $events[RequestEvent::class]);
    }

    private function createRequestEvent(Request $request, bool $isMainRequest = true): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $requestType = $isMainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST;

        return new RequestEvent($kernel, $request, $requestType);
    }

    private function setUpRequestWithMaxIddleTimePassed(): Request
    {
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        $this->session->start();
        $pastTime = time() - $this->maxIdleTime - 100;
        $this->session->set('last_activity', $pastTime);

        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack->push($request);
        $this->urlGenerator->method('generate')->with('home')->willReturn('/home');

        return $request;
    }
}
