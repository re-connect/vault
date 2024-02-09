<?php

namespace App\Tests\v2\Controller\GdprController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class ImprovePasswordTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/improve-password';

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

    public function provideTestRoute(): \Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as pro' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
        yield 'Should return 200 status code when authenticated as beneficiary' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
    }

    /**  @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    public function provideTestFormIsValid(): \Generator
    {
        yield 'Should redirect when password successfully updated for beneficiary' => [
            self::URL,
            'submit',
            [
                'change_password_form[plainPassword][first]' => '123456Aaa',
                'change_password_form[plainPassword][second]' => '123456Aaa',
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL,
            '/user/redirect-user/',
        ];

        yield 'Should redirect when password successfully updated for pro' => [
            self::URL,
            'submit',
            [
                'change_password_form[plainPassword][first]' => '123456Aaaa',
                'change_password_form[plainPassword][second]' => '123456Aaaa',
            ],
            MemberFixture::MEMBER_MAIL,
            '/user/redirect-user/',
        ];
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

    public function provideTestFormIsNotValid(): \Generator
    {
        $values = [
            'change_password_form[plainPassword][first]' => '123456Aa',
            'change_password_form[plainPassword][second]' => '123457Az',
        ];

        yield 'Should return an error if passwords does not match for pro' => [
            self::URL,
            'improve_password',
            'submit',
            $values,
            [
                [
                    'message' => 'passwords_mismatch',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];

        yield 'Should return an error if passwords does not match for beneficiary' => [
            self::URL,
            'improve_password',
            'submit',
            $values,
            [
                [
                    'message' => 'passwords_mismatch',
                    'params' => null,
                ],
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL,
        ];

        $values = [
            'change_password_form[plainPassword][first]' => '123456789',
            'change_password_form[plainPassword][second]' => '123456789',
        ];

        yield 'Should return an error if password does not meet characters requirements for pro' => [
            self::URL,
            'improve_password',
            'submit',
            $values,
            [
                [
                    'message' => 'password_help_criteria',
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

        yield 'Should return an error if password does not meet characters requirements for beneficiary' => [
            self::URL,
            'improve_password',
            'submit',
            $values,
            [
                [
                    'message' => 'password_help_criteria',
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
            BeneficiaryFixture::BENEFICIARY_MAIL,
        ];

        $values = [
            'change_password_form[plainPassword][first]' => 'AAAAaaaaa',
            'change_password_form[plainPassword][second]' => 'AAAAaaaaa',
        ];

        yield 'Should return an error if password does not contains non alphanumeric for pro' => [
            self::URL,
            'improve_password',
            'submit',
            $values,
            [
                [
                    'message' => 'password_help_criteria',
                    'params' => null,
                ],
                [
                    'message' => 'password_criterion_nonAlphabetic',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];

        yield 'Should return an error if password does not contains non alphanumeric beneficiary' => [
            self::URL,
            'improve_password',
            'submit',
            $values,
            [
                [
                    'message' => 'password_help_criteria',
                    'params' => null,
                ],
                [
                    'message' => 'password_criterion_nonAlphabetic',
                    'params' => null,
                ],
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL,
        ];
    }

    public function testUpdatePasswordHydrateUser(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_PASSWORD_WEAK)->object();

        self::assertFalse($user->hasPasswordWithLatestPolicy());

        $this->assertFormIsValid(
            self::URL,
            'submit',
            [
                'change_password_form[plainPassword][first]' => '123456Aaa',
                'change_password_form[plainPassword][second]' => '123456Aaa',
            ],
            BeneficiaryFixture::BENEFICIARY_PASSWORD_WEAK,
            '/user/redirect-user/',
        );

        self::assertTrue(UserFactory::find($user)->object()->hasPasswordWithLatestPolicy());
    }
}
