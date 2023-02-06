<?php

namespace App\Tests\v2\Controller\BeneficiaryCreationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class BeneficiaryCreationStep6Test extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/beneficiary/create/6/%s';

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate(['isCreating' => true, 'remotely' => false])->object();
        $url = sprintf($url, $creationProcess->getId());
        $expectedRedirect = sprintf($expectedRedirect, $creationProcess->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should redirect when authenticated as professional' => [self::URL, 302, MemberFixture::MEMBER_MAIL_WITH_RELAYS, '/beneficiary/create/download-terms-of-use/%s'];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
    }
}
