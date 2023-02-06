<?php

namespace App\Tests\v2\Controller\ContactController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class EditTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/contact/%s/edit';
    private const FORM_VALUES = [
        'contact[nom]' => 'new lastname',
        'contact[prenom]' => 'new firstname',
        'contact[telephone]' => '777777777',
        'contact[email]' => BeneficiaryFixture::BENEFICIARY_MAIL,
        'contact[commentaire]' => 'A prévenir en cas de besoin',
        'contact[association]' => 'SOS Solidarité',
        'contact[bPrive]' => true,
    ];

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
        yield 'Should return 403 status code when authenticated as member' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should refresh when valid' => [
            self::URL,
            'confirm',
            self::FORM_VALUES,
            BeneficiaryFixture::BENEFICIARY_MAIL,
            '/beneficiary/%s/contacts',
        ];
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        $values = self::FORM_VALUES;
        $values['contact[nom]'] = '';

        yield 'Should return an error when nom is empty' => [
            self::URL,
            'contact_edit',
            'confirm',
            $values,
            [
                [
                    'message' => 'This value should not be blank.',
                    'params' => null,
                ],
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL,
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['contact[prenom]'] = '';

        yield 'Should return an error when prenom is empty' => [
            self::URL,
            'contact_edit',
            'confirm',
            $values,
            [
                [
                    'message' => 'This value should not be blank.',
                    'params' => null,
                ],
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL,
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['contact[email]'] = 'wrong format';

        yield 'Should return an error when email is not right format' => [
            self::URL,
            'contact_edit',
            'confirm',
            $values,
            [
                [
                    'message' => 'This value is not a valid email address.',
                    'params' => null,
                ],
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL,
            'div.invalid-feedback',
        ];
    }

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf($url, $beneficiary->getContacts()[0]->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    /**  @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf($url, $beneficiary->getContacts()[0]->getId());
        $redirectUrl = $redirectUrl ? sprintf($redirectUrl, $beneficiary->getId()) : '';
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    /**
     * @param array<string, string> $values
     * @param array<array>          $errors
     *
     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf($url, $beneficiary->getContacts()[0]->getId());
        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }
}
