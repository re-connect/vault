<?php

namespace App\Tests\v2\Controller\EventController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class CreateTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    public const URL = '/beneficiary/%s/event/create';

    private const FORM_VALUES = [
        'event[nom]' => 'RDV CAF',
        'event[timezone]' => 'Europe/Paris',
        'event[lieu]' => 'CAF rue de Paris',
        'event[commentaire]' => 'Apporter documents',
    ];

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
    }

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
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

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should redirect when form is correct' => [
            self::URL,
            'confirm',
            $this->getTestValues(),
            BeneficiaryFixture::BENEFICIARY_MAIL,
            '/beneficiary/%d/events',
        ];
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
        $url = sprintf($url, $beneficiary->getId());
        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        $values = $this->getTestValues();
        $values['event[nom]'] = '';
        yield 'Should return an error when nom is empty' => [
            self::URL,
            'event_create',
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

        $values = $this->getTestValues();
        $values['event[date]'] = (new \DateTime('yesterday'))->format('Y-m-d H:i:s');
        yield 'Should return an error when date is in the past' => [
            self::URL,
            'event_create',
            'confirm',
            $values,
            [
                [
                    'message' => 'La date de l\'événement est déjà passée.',
                    'params' => null,
                ],
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL,
            'div.invalid-feedback',
        ];
    }

    private function getTestValues(): array
    {
        $values = self::FORM_VALUES;
        $values['event[date]'] = (new \DateTime('tomorrow'))->format('Y-m-d H:i:s');

        return $values;
    }
}
