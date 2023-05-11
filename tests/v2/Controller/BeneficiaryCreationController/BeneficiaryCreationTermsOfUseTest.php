<?php

namespace App\Tests\v2\Controller\BeneficiaryCreationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class BeneficiaryCreationTermsOfUseTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/beneficiary/create/download-terms-of-use/%s';

    /** @dataProvider provideTestRoute */
    public function testRoute(
        string $url,
        int $expectedStatusCode,
        ?string $userMail = null,
        ?string $expectedRedirect = null,
        string $method = 'GET',
        bool $isXmlHttpRequest = false,
        array $body = [],
    ): void {
        $creationProcess = BeneficiaryCreationProcessFactory::createOne(['isCreating' => false, 'remotely' => false])->object();
        $url = sprintf($url, $creationProcess->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as professional' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS];
        yield 'Should redirect when authenticated as beneficiary' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary/home'];
    }
}
