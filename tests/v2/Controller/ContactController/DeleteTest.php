<?php

namespace App\Tests\v2\Controller\ContactController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class DeleteTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/contact/%s/delete';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should redirect after delete when authenticated as beneficiaire' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary/%s/contacts'];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
        yield 'Should return 403 status code when authenticated as member' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
    }

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();

        $url = sprintf($url, $beneficiary->getContacts()[0]->getId());
        $expectedRedirect = $expectedRedirect ? sprintf($expectedRedirect, $beneficiary->getId()) : '';
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }
}
