<?php

namespace App\Tests\v2\Controller\NoteController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class DetailTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/note/%s/detail';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code when authenticated as member with relay in common' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should redirect when authenticated as an other beneficiaire' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, '/beneficiary/home'];
        yield 'Should redirect when authenticated as member with no relay in common' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/professional/beneficiaries'];
    }

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
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $publicNote = NoteFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => false])->object();

        $url = sprintf($url, $publicNote->getId());
        $expectedRedirect = $expectedRedirect ? sprintf($expectedRedirect, $beneficiary->getId()) : '';
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        // Also check that authorized Pro can't update private data
        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES === $userMail) {
            $privateNote = NoteFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => true])->object();
            $newUrl = sprintf(self::URL, $privateNote->getId());
            $this->assertRoute($newUrl, 302, $userMail, '/professional/beneficiaries', $method);
        }
    }
}
