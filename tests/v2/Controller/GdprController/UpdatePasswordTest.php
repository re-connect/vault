<?php

namespace App\Tests\v2\Controller\GdprController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class UpdatePasswordTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/update-password';

    public function provideTestRoute(): \Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as member' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
        yield 'Should return 200 status code when authenticated as beneficiary' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
    }

    public function provideTestFormIsValid(): \Generator
    {
        yield 'Should redirect when passwords match with required criteria' => [
            self::URL,
            'submit',
            [
                'change_password_form[plainPassword][first]' => '123456Aa',
                'change_password_form[plainPassword][second]' => '123456Aa',
            ],
            MemberFixture::MEMBER_MAIL,
            '/login-end',
        ];
    }

    public function provideTestFormIsNotValid(): \Generator
    {
        $values = [
            'change_password_form[plainPassword][first]' => '123456Aa',
            'change_password_form[plainPassword][second]' => '123457Az',
        ];
        yield 'Should return an error if passwords does not match' => [
            self::URL,
            'app_update_password',
            'submit',
            $values,
            [
                [
                    'message' => 'form.validation.mismatch',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];
        $values = [
            'change_password_form[plainPassword][first]' => '1234',
            'change_password_form[plainPassword][second]' => '1234',
        ];
        yield 'Should return an error if password is too short' => [
            self::URL,
            'app_update_password',
            'submit',
            $values,
            [
                [
                    'message' => 'form.validation.tooShort',
                    'params' => ['{{ limit }}' => 8],
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];
        $values = [
            'change_password_form[plainPassword][first]' => '123456789',
            'change_password_form[plainPassword][second]' => '123456789',
        ];
        yield 'Should return an error if password does not meet characters requirements' => [
            self::URL,
            'app_update_password',
            'submit',
            $values,
            [
                [
                    'message' => 'password_help_criteria',
                    'params' => [
                        '{{ atLeast }}' => 2,
                        '{{ total }}' => 3,
                    ],
                ],
                [
                    'message' => 'password_criterion_special',
                    'params' => null,
                ],
                [
                    'message' => 'password_criterion_lowercase',
                    'params' => null,
                ],
                [
                    'message' => 'password_criterion_uppercase',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];
    }

    /** @dataProvider provideTestRoute */
    public function testRoute(
        string $url,
        int $expectedStatusCode,
        string $userMail = null,
        string $expectedRedirect = null,
        string $method = 'GET',
        bool $isXmlHttpRequest = false,
        array $body = [],
    ): void {
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    /**  @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    /**
     * @param array<string, string>         $values
     * @param array<array<string, ?string>> $errors
     *
     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, string $alternateSelector = null): void
    {
        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email);
    }
}
