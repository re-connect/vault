<?php

namespace App\Tests\v2\Controller\BeneficiaryAffiliationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class DisaffiliateTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/beneficiary/%s/disaffiliate';
    private const FORM_VALUES = [
        'disaffiliate_beneficiary[relays][0]' => '',
        'disaffiliate_beneficiary[relays][1]' => '',
    ];

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
        $url = sprintf($url, $beneficiary->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 403 status code when authenticated as beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should redirect to beneficiary list when authenticated as member with 1 relay in common' => [self::URL, 302, MemberFixture::MEMBER_MAIL_WITH_UNIQUE_RELAY_SHARED_WITH_BENEFICIARIES, '/professional/beneficiaries'];
        yield 'Should return 200 status code when authenticated as member with multiple relay in common' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
    }

    /**  @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        // Pro and beneficiary share multiple relays
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $relays = $beneficiary->getCentres();
        $initialRelaysCount = count($relays);

        $url = sprintf($url, $beneficiary->getId());

        $values = [
            'disaffiliate_beneficiary[relays][0]' => $relays[0]->getId(),
            'disaffiliate_beneficiary[relays][1]' => $relays[1]->getId(),
        ];

        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);

        // Assert that actual relay count doesn't match inital count
        self::assertNotCount($initialRelaysCount, BeneficiaireFactory::find(['id' => $beneficiary->getId()])->object()->getCentres());
    }


    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should redirect on pro home' => [
            self::URL,
            'submit',
            self::FORM_VALUES,
            MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
            '/professional/beneficiaries',
        ];
    }

    /**  @dataProvider provideTestFormIsNotValid */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf($url, $beneficiary->getId());
        $values = [];

        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        yield 'Should return an error if no selected relays' => [
            self::URL,
            'disaffiliate_beneficiary',
            'submit',
            self::FORM_VALUES,
            [
                [
                    'message' => 'beneficiary_disaffiliation_empty_relays',
                ],
            ],
            MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
            'div.invalid-feedback',
        ];
    }
}
