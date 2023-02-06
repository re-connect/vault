<?php

namespace App\Tests\v2\Controller\BeneficiaryCreationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class BeneficiaryCreationStep1Test extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/beneficiary/create/1';
    private const FORM_VALUES = [
        'create_beneficiary[user][prenom]' => 'Jean',
        'create_beneficiary[user][nom]' => 'Dupont',
        'create_beneficiary[user][telephone]' => '0666666666',
        'create_beneficiary[user][email]' => 'jdupont@mail.com',
        'create_beneficiary[dateNaissance][day]' => '1',
        'create_beneficiary[dateNaissance][month]' => '1',
        'create_beneficiary[dateNaissance][year]' => '1970',
    ];

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as professional' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
    }

    /**  @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $nextCreatedBeneficiaryId = BeneficiaryCreationProcessFactory::createOne()->object()->getId() + 1;
        $redirectUrl = sprintf($redirectUrl, $nextCreatedBeneficiaryId);
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should redirect to step 2 when form is correct' => [
            self::URL,
            'confirm',
            self::FORM_VALUES,
            MemberFixture::MEMBER_MAIL,
            '/beneficiary/create/2/%s',
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
        $values['create_beneficiary[user][nom]'] = '';

        yield 'Should return an error when nom is empty' => [
            self::URL,
            'create_beneficiary',
            'confirm',
            $values,
            [
                [
                    'message' => 'lastname_not_empty',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['create_beneficiary[user][prenom]'] = '';

        yield 'Should return an error when prenom is empty' => [
            self::URL,
            'create_beneficiary',
            'confirm',
            $values,
            [
                [
                    'message' => 'firstname_not_empty',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['create_beneficiary[user][email]'] = MemberFixture::MEMBER_MAIL;

        yield 'Should return an error if email already used' => [
            self::URL,
            'create_beneficiary',
            'confirm',
            $values,
            [
                [
                    'message' => 'email_already_in_use',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['create_beneficiary[user][email]'] = 'wrongFormattedEmail';

        yield 'Should return an error if email is not correct' => [
            self::URL,
            'create_beneficiary',
            'confirm',
            $values,
            [
                [
                    'message' => 'This value is not a valid email address.',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];
    }
}
