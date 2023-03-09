<?php

namespace App\Tests\v2\Controller\ProfessionalController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class SearchBeneficiariesTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/professional/beneficiaries/search';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as member' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
    }

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        self::ensureKernelShutdown();
        $clientTest = static::createClient();

        if ($userMail) {
            $user = $this->getTestUserFromDb($userMail);
            $clientTest->loginUser($user);
        }

        $clientTest->xmlHttpRequest('POST', $url, [
            'filter_beneficiary' => [
                'search' => '',
                'relay' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame($expectedStatusCode);
        if ($expectedRedirect) {
            $this->assertResponseRedirects($expectedRedirect);
        }
    }
}
