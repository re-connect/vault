<?php

namespace App\Tests\v2\Controller\BeneficiaryAffiliationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class SearchTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/beneficiary/affiliate/search';
    private const FORM_VALUES = [
        'search_beneficiary[lastname]' => 'a',
        'search_beneficiary[firstname]' => 'a',
        'search_beneficiary[birthDate][day]' => '1',
        'search_beneficiary[birthDate][month]' => '1',
        'search_beneficiary[birthDate][year]' => '1975',
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
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as professional' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
        yield 'Should redirect when authenticated as beneficiary' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary/home'];
    }

    /**  @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should stay on same page and display results' => [
            self::URL,
            'search',
            self::FORM_VALUES,
            MemberFixture::MEMBER_MAIL,
            null,
        ];
    }

    /**
     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        $values = self::FORM_VALUES;
        $values['search_beneficiary[birthDate][day]'] = '';

        yield 'Should return an error when day is empty' => [
            self::URL,
            'affiliate_beneficiary_search',
            'search',
            $values,
            [
                [
                    'message' => 'Please enter a valid birthdate.',
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['search_beneficiary[birthDate][month]'] = '';

        yield 'Should return an error when month is empty' => [
            self::URL,
            'affiliate_beneficiary_search',
            'search',
            $values,
            [
                [
                    'message' => 'Please enter a valid birthdate.',
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['search_beneficiary[birthDate][year]'] = '';

        yield 'Should return an error when year is empty' => [
            self::URL,
            'affiliate_beneficiary_search',
            'search',
            $values,
            [
                [
                    'message' => 'Please enter a valid birthdate.',
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];
    }
}
