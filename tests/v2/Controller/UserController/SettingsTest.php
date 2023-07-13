<?php

namespace App\Tests\v2\Controller\UserController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class SettingsTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/user/settings';
    private const FORM_VALUES = [
        'user[prenom]' => 'Ambroise',
        'user[nom]' => 'Croizat',
        'user[telephone]' => '0618181818',
        'user[email]' => 'a.croizat@mail.com',
        'user[adresse][nom]' => 'Bastille',
        'user[adresse][ville]' => 'Paris',
        'user[adresse][codePostal]' => '75011',
        'user[dateNaissance][day]' => '1',
        'user[dateNaissance][month]' => '1',
        'user[dateNaissance][year]' => '1995',
        'user[secretQuestion][questionSecreteChoice]' => 'Quel est le prénom de la mère du bénéficiaire ?',
        'user[secretQuestion][autreQuestionSecrete]' => '',
        'user[secretQuestion][reponseSecrete]' => 'Maman',
    ];

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated on settings page' => [self::URL, 302, null, '/login'];
        yield 'Should access settings when authenticated as member' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
        yield 'Should access settings when authenticated as beneficiary' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should refresh when form is correct' => [
            self::URL,
            'submit',
           self::FORM_VALUES,
            BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT,
            self::URL,
        ];
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        $values = self::FORM_VALUES;
        $values['user[nom]'] = '';
        yield 'Should return an error if lastname is empty' => [
            self::URL,
            'user_settings',
            'submit',
            $values,
            [
                [
                    'message' => 'lastname_not_empty',
                    'params' => null,
                ],
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS,
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['user[prenom]'] = '';
        yield 'Should return an error if firstname is empty' => [
            self::URL,
            'user_settings',
            'submit',
          $values,
            [
                [
                    'message' => 'firstname_not_empty',
                    'params' => null,
                ],
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS,
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['user[email]'] = 'wrong format';
        yield 'Should return an error if email address is not correct' => [
            self::URL,
            'user_settings',
            'submit',
            $values,
            [
                [
                    'message' => 'This value is not a valid email address.',
                    'params' => null,
                ],
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS,
            'div.invalid-feedback',
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

    /**
     * @param array<string,string> $values
     *
     * @dataProvider provideTestFormIsValid
     */
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
        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }
}
