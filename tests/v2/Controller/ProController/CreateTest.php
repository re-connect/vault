<?php

namespace App\Tests\v2\Controller\ProController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class CreateTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/pro/create';

    private const FORM_VALUES = [
        'create_user[prenom]' => 'Jean',
        'create_user[nom]' => 'Dupont',
        'create_user[telephone]' => '0666666666',
        'create_user[email]' => 'mail@test.com',
        'create_user[plainPassword][first]' => 'Reconnect!',
        'create_user[plainPassword][second]' => 'Reconnect!',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
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
        $nextUserId = UserFactory::createOne()->object()->getId() + 1;
        $redirectUrl = sprintf($redirectUrl, $nextUserId);
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should redirect to invite page when form is correct' => [
            self::URL,
            'submit',
            self::FORM_VALUES,
            MemberFixture::MEMBER_MAIL,
            '/user/%s/invite',
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
        $values['create_user[nom]'] = '';

        yield 'Should return an error when nom is empty' => [
            self::URL,
            'create_pro',
            'submit',
            $values,
            [
                [
                    'message' => 'lastname_not_empty',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];

        $values = self::FORM_VALUES;
        $values['create_user[prenom]'] = '';

        yield 'Should return an error when prenom is empty' => [
            self::URL,
            'create_pro',
            'submit',
            $values,
            [
                [
                    'message' => 'firstname_not_empty',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];

        $values = self::FORM_VALUES;
        $values['create_user[plainPassword][first]'] = '';
        $values['create_user[plainPassword][second]'] = '';

        yield 'Should return an error when password is empty' => [
            self::URL,
            'create_pro',
            'submit',
            $values,
            [
                [
                    'message' => 'form_validation_no_password',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];

        $values = self::FORM_VALUES;
        $values['create_user[plainPassword][first]'] = 'first';
        $values['create_user[plainPassword][second]'] = 'second';

        yield 'Should return an error when password fields does not match' => [
            self::URL,
            'create_pro',
            'submit',
            $values,
            [
                [
                    'message' => 'The values do not match.',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];

        $values = self::FORM_VALUES;
        $values['create_user[plainPassword][first]'] = 'password';
        $values['create_user[plainPassword][second]'] = 'password';

        yield 'Should return an error when password is too weak' => [
            self::URL,
            'create_pro',
            'submit',
            $values,
            [
                [
                    'message' => 'password_help_criteria',
                    'params' => ['{{ atLeast }}' => 2, '{{ total }}' => 3],
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];

        $values = self::FORM_VALUES;
        $values['create_user[plainPassword][first]'] = 'Reco!';
        $values['create_user[plainPassword][second]'] = 'Reco!';

        yield 'Should return an error when password is too short' => [
            self::URL,
            'create_pro',
            'submit',
            $values,
            [
                [
                    'message' => 'Cette chaîne est trop courte. Elle doit avoir au minimum 8 caractères.',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];
    }
}
