<?php

namespace App\Tests\v2\Controller\BeneficiaryResetPasswordController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\ResetPasswordRequestFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class SecretAnswerTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/beneficiary/%s/reset-password/secret-answer';
    private const FORM_VALUES = [
        'reset_password_secret_answer[secretAnswer]' => '',
        'reset_password_secret_answer[password][plainPassword][first]' => '',
        'reset_password_secret_answer[password][plainPassword][second]' => '',
    ];

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 403 status code when authenticated as beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code when authenticated as member with relay in common' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
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
        $url = sprintf(self::URL, $beneficiary->getId());

        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    /**  @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf(self::URL, $beneficiary->getId());
        $values['reset_password_secret_answer[secretAnswer]'] = $beneficiary->getReponseSecrete();

        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        $values = self::FORM_VALUES;
        $values['reset_password_secret_answer[password][plainPassword][first]'] = 'sameCorrectPassword';
        $values['reset_password_secret_answer[password][plainPassword][second]'] = 'sameCorrectPassword';

        yield 'Should redirect to beneficiaries list when form is correct' => [
            self::URL,
            'confirm',
            $values,
            MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
            '/professional/beneficiaries',
        ];
    }

    /**
     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf(self::URL, $beneficiary->getId());

        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        $values = self::FORM_VALUES;
        $values['reset_password_secret_answer[password][plainPassword][first]'] = 'sameCorrectPassword';
        $values['reset_password_secret_answer[password][plainPassword][second]'] = 'sameCorrectPassword';
        $values['reset_password_secret_answer[secretAnswer]'] = 'wrongSecretAnswer';

        yield 'Should return an error when secretQuestion is not correct' => [
            self::URL,
            'reset_password_beneficiary_secret_answer',
            'confirm',
            $values,
            [
                [
                    'message' => 'secret_answer_mismatch',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
            'div.invalid-feedback',
        ];

        $values['reset_password_secret_answer[password][plainPassword][first]'] = 'differentPassword';
        // Correct secret answer declared in BeneficiaireFactory
        $values['reset_password_secret_answer[secretAnswer]'] = 'reponse';

        yield 'Should return an error when passwords mismatch' => [
            self::URL,
            'reset_password_beneficiary_secret_answer',
            'confirm',
            $values,
            [
                [
                    'message' => 'Les deux mots de passe ne sont pas identiques',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
            'div.invalid-feedback',
        ];

        $values['reset_password_secret_answer[password][plainPassword][first]'] = 'aaa';
        $values['reset_password_secret_answer[password][plainPassword][second]'] = 'aaa';

        yield 'Should return an error when password is too short' => [
            self::URL,
            'reset_password_beneficiary_secret_answer',
            'confirm',
            $values,
            [
                [
                    'message' => 'Votre mot de passe doit contenir au moins 5 caractères',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
            'div.invalid-feedback',
        ];
    }
}
