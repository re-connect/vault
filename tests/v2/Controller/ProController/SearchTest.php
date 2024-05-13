<?php

namespace App\Tests\v2\Controller\ProController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class SearchTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/pro/search';
    private const FORM_VALUES = [
        'search_pro[lastname]' => 'gollum',
        'search_pro[firstname]' => 'smeagol',
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
        yield 'Should return 200 status code when authenticated as member' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS];
        yield 'Should return 403 status code when authenticated as member without permissions' => [self::URL, 403, MemberFixture::MEMBER_MAIL_NO_RELAY_NO_PERMISSION];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
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
            sprintf('%s?firstname=%s&lastname=%s', self::URL, 'smeagol', 'gollum'),
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
        $values['search_pro[lastname]'] = '';

        yield 'Should return an when lastname is blank' => [
            self::URL,
            'search_pro',
            'search',
            $values,
            [
                [
                    'message' => 'This value should not be blank.',
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];

        $values = self::FORM_VALUES;
        $values['search_pro[firstname]'] = '';
        yield 'Should return an when firstname is blank' => [
            self::URL,
            'search_pro',
            'search',
            $values,
            [
                [
                    'message' => 'This value should not be blank.',
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];
    }
}
