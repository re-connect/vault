<?php

namespace App\Tests\v2\Service;

use App\Entity\User;
use App\ServiceV2\GdprService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class GdprServiceTest extends TestCase
{
    private GdprService $gdprService;
    private Security|MockObject $security;

    protected function setUp(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $this->security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $this->gdprService = new GdprService($requestStack, $this->security, $translator, true);
    }

    /** @dataProvider  provideTestGetDaysBeforeExpiration */
    public function testGetDaysBeforeExpiration(\DateTimeImmutable $updatedAt, int $expectedResult): void
    {
        $user = new User();
        $reflector = new \ReflectionClass(GdprService::class);
        $method = $reflector->getMethod('getDaysBeforeExpiration');

        $user->setPasswordUpdatedAt($updatedAt);
        $this->security->method('getUser')->willReturn($user);
        $result = $method->invokeArgs($this->gdprService, []);

        // If leap year, month >= March, and password updated less than a year ago, increments expected result
        if (date('L') && date('n') >= 3 && $updatedAt->add(new \DateInterval('P1Y')) >= new \DateTime()) {
            ++$expectedResult;
        }

        $this->assertEquals($expectedResult, $result);
    }

    public function provideTestGetDaysBeforeExpiration(): \Generator
    {
        yield 'Should return 365 when password has been updated today' => [new \DateTimeImmutable('now'), 365];
        yield 'Should return 5 when password has been updated 360 days ago' => [(new \DateTimeImmutable('now'))->sub(new \DateInterval('P360D')), 5];
        yield 'Should return 0 when password has been updated a year ago' => [(new \DateTimeImmutable('now'))->sub(new \DateInterval('P1Y')), 0];
        yield 'Should return 0 when password has been updated two year ago' => [(new \DateTimeImmutable('now'))->sub(new \DateInterval('P2Y')), 0];
    }

    /** @dataProvider provideTestIsPasswordRenewalDue */
    public function testIsPasswordRenewalDue(\DateTimeImmutable $updatedAt, bool $expectedResult): void
    {
        $user = new User();
        $user->setPasswordUpdatedAt($updatedAt);
        $this->security->method('getUser')->willReturn($user);

        $this->assertEquals($expectedResult, $this->gdprService->isPasswordRenewalDue());
    }

    public function provideTestIsPasswordRenewalDue(): \Generator
    {
        yield 'Should return true when password has been updated 360 days ago' => [(new \DateTimeImmutable('now'))->sub(new \DateInterval('P360D')), true];
        yield 'Should return false when password has been updated today' => [new \DateTimeImmutable('now'), false];
        yield 'Should return false when password has been updated 3 days ago' => [(new \DateTimeImmutable('now'))->sub(new \DateInterval('P3D')), false];
    }

    /** @dataProvider provideTestIsPasswordExpired */
    public function testIsPasswordExpired(\DateTimeImmutable $updatedAt, bool $expectedResult): void
    {
        $user = new User();
        $user->setPasswordUpdatedAt($updatedAt);
        $this->security->method('getUser')->willReturn($user);

        $this->assertEquals($expectedResult, $this->gdprService->isPasswordExpired());
    }

    public function provideTestIsPasswordExpired(): \Generator
    {
        yield 'Should return true when password has been updated a year ago' => [(new \DateTimeImmutable('now'))->sub(new \DateInterval('P1Y')), true];
        yield 'Should return false when password has been updated today' => [new \DateTimeImmutable('now'), false];
        yield 'Should return false when password has been updated 3 days ago' => [(new \DateTimeImmutable('now'))->sub(new \DateInterval('P3D')), false];
    }
}
