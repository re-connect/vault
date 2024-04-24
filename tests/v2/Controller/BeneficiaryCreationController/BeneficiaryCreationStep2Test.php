<?php

namespace App\Tests\v2\Controller\BeneficiaryCreationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class BeneficiaryCreationStep2Test extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/beneficiary/create/2/%s';
    private const FORM_VALUES = [
        'create_beneficiary[password]' => 'Password1',
        'create_beneficiary[mfaEnabled]' => false,
        'create_beneficiary[mfaMethod]' => 'email',
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
        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate(['isCreating' => true, 'remotely' => false])->object();
        $url = sprintf($url, $creationProcess->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as professional' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
    }

    /**
     * @param array<string, string> $values
     *
     * @dataProvider provideTestFormIsValid
     */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate(['isCreating' => true, 'remotely' => false])->object();
        $url = sprintf($url, $creationProcess->getId());
        $redirectUrl = sprintf($redirectUrl, $creationProcess->getId());
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should redirect to step 2 when form is correct' => [
            self::URL,
            'submit',
            self::FORM_VALUES,
            MemberFixture::MEMBER_MAIL,
            '/beneficiary/create/3/%s',
        ];

        $values = self::FORM_VALUES;
        $values['create_beneficiary[mfaEnabled]'] = true;
        $values['create_beneficiary[mfaMethod]'] = 'email';
        yield 'Should redirect to step 2 when form is correct with mfa enabled' => [
            self::URL,
            'submit',
            $values,
            MemberFixture::MEMBER_MAIL,
            '/beneficiary/create/3/%s',
        ];
    }

    /**
     * @param array<string, string>         $values
     * @param array<array<string, ?string>> $errors
     *
     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate(['isCreating' => true, 'remotely' => false])->object();
        $url = sprintf($url, $creationProcess->getId());
        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        $values = self::FORM_VALUES;
        $values['create_beneficiary[password]'] = '';

        yield 'Should return an error when password is empty' => [
            self::URL,
            'create_beneficiary',
            'submit',
            $values,
            [
                [
                    'message' => 'form_validation_no_password',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['create_beneficiary[password]'] = '1234';

        yield 'Should return an error when password length < 5' => [
            self::URL,
            'create_beneficiary',
            'submit',
            $values,
            [
                [
                    'message' => 'Votre mot de passe doit contenir au moins 9 caractères',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];

        $values['create_beneficiary[password]'] = 'aaaaaaaaa';
        yield 'Should return an error if password does not meet characters requirements' => [
            self::URL,
            'create_beneficiary',
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
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['create_beneficiary[mfaEnabled]'] = true;
        $values['create_beneficiary[mfaMethod]'] = 'sms';
        yield 'Should return an error when enabling sms 2fa with no phone' => [
            self::URL,
            'create_beneficiary',
            'submit',
            $values,
            [
                [
                    'message' => 'Vous avez choisi de recevoir un code de connexion par SMS, mais vous n\'avez pas renseigné de numéro de téléphone.',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];
    }
}
