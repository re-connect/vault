<?php

namespace App\Tests\v2\Service;

use App\Entity\User;
use App\ServiceV2\ActivityLogger;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLoggerTest extends TestCase
{
    private Security|MockObject $security;
    private ActivityLogger $activityLogger;
    private TestHandler $testHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $logger = new Logger('test');
        $this->testHandler = new TestHandler();
        $logger->pushHandler($this->testHandler);
        $this->security = $this->createMock(Security::class);
        $this->activityLogger = new ActivityLogger($logger, $this->createMock(RequestStack::class));
    }

    public function testLogLoginShouldLog(): void
    {
        $this->activityLogger->logLogin(new User());

        $this->assertTrue($this->testHandler->hasInfoThatContains('User logged in'));
    }
}
