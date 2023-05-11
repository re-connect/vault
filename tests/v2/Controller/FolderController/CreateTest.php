<?php

namespace App\Tests\v2\Controller\FolderController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class CreateTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/beneficiary/%s/folder/create';
    private const FORM_VALUES = [
        'folder[nom]' => 'Emploi',
    ];

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code when authenticated as member with relay in common' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should redirect when authenticated as an other beneficiaire' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, '/beneficiary/home'];
        yield 'Should redirect when authenticated as member with no relay in common' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/professional/beneficiaries'];
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should refresh when form is correct' => [
            self::URL,
            'confirm',
            self::FORM_VALUES,
            BeneficiaryFixture::BENEFICIARY_MAIL,
            '/beneficiary/%s/documents',
        ];
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        $values = self::FORM_VALUES;
        $values['folder[nom]'] = '';

        yield 'Should return an error when nom is empty' => [
            self::URL,
            'folder_create',
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
        $url = sprintf($url, $beneficiary->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    /**  @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf($url, $beneficiary->getId());
        $redirectUrl = $redirectUrl ? sprintf($redirectUrl, $beneficiary->getId()) : '';
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    /**
     * @param array<string, string>         $values
     * @param array<array<string, ?string>> $errors
     *
     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf($url, $beneficiary->getId());
        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }
}
