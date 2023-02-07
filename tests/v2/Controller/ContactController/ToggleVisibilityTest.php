<?php

namespace App\Tests\v2\Controller\ContactController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\v2\Controller\AbstractControllerTest;

class ToggleVisibilityTest extends AbstractControllerTest
{
    private const URL = '/contact/%s/toggle-visibility';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login', 'PATCH'];
        yield 'Should return 204 status code when authenticated as beneficiaire' => [self::URL, 204, BeneficiaryFixture::BENEFICIARY_MAIL, null, 'PATCH'];
        yield 'Should return 204 status when authenticated as member with relay in common' => [self::URL, 204, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, null, 'PATCH'];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, null, 'PATCH'];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL, null, 'PATCH'];
    }

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $contact = ContactFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => false])->object();
        $url = sprintf($url, $contact->getId());
        $expectedRedirect = $expectedRedirect ? sprintf($expectedRedirect, $beneficiary->getId()) : '';
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, 'PATCH', true);

        // Also check that authorized Pro can't update private data
        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES === $userMail) {
            $privateContact = ContactFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => true])->object();
            $newUrl = sprintf(self::URL, $privateContact->getId());
            $this->assertRoute($newUrl, 403, $userMail, null, $method, true);
        }
    }
}
