<?php

namespace App\Tests\v2\Controller\BeneficiaryAffiliationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class RelaysTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/beneficiary/%s/affiliate/relays';
    private const FORM_VALUES = [
        'affiliate_beneficiary[relays][0]' => '',
        'affiliate_beneficiary[secretAnswer]' => '',
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
        $beneficiary = BeneficiaireFactory::createOne()->object();
        $url = sprintf($url, $beneficiary->getId());
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
        // With correct secret answer
        $professional = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS)->object();
        $beneficiary = BeneficiaireFactory::createOne()->object();
        $url = sprintf($url, $beneficiary->getId());
        $relays = $professional->getCentres();
        $values = [
            'affiliate_beneficiary[relays][0]' => $relays[0]->getId(),
            'affiliate_beneficiary[relays][1]' => $relays[1]->getId(),
            'affiliate_beneficiary[relays][2]' => $relays[2]->getId(),
            'affiliate_beneficiary[secretAnswer]' => $beneficiary->getReponseSecrete(),
        ];

        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);

        $this->assertCount(3, $beneficiary->getCentres());
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals($relays[$i]->getId(), $beneficiary->getCentres()[$i]->getId());
            // secret question is correct, invitation is accepted directly
            foreach ($beneficiary->getBeneficiairesCentres() as $beneficiaryRelay) {
                if ($beneficiaryRelay->getCentre() === $beneficiary->getCentres()[$i]) {
                    $this->assertTrue($beneficiaryRelay->getBValid());
                }
            }
        }
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should redirect on pro home' => [
            self::URL,
            'submit',
            self::FORM_VALUES,
            MemberFixture::MEMBER_MAIL_WITH_RELAYS,
            '/professional/beneficiaries',
        ];
    }

    public function testFormWithEmptySecretQuestion(): void
    {
        // With empty secret answer
        $professional = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS)->object();
        $beneficiary = BeneficiaireFactory::createOne()->object();
        $url = sprintf(self::URL, $beneficiary->getId());
        $relays = $professional->getCentres();
        $values = [
            'affiliate_beneficiary[relays][0]' => $relays[0]->getId(),
            'affiliate_beneficiary[relays][1]' => $relays[1]->getId(),
            'affiliate_beneficiary[relays][2]' => $relays[2]->getId(),
            'affiliate_beneficiary[secretAnswer]' => '',
        ];

        $this->assertFormIsValid($url, 'submit', $values, MemberFixture::MEMBER_MAIL_WITH_RELAYS, '/professional/beneficiaries');

        $this->assertCount(3, $beneficiary->getCentres());
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals($relays[$i]->getId(), $beneficiary->getCentres()[$i]->getId());
            // secret question is empty, invitation is pending
            foreach ($beneficiary->getBeneficiairesCentres() as $beneficiaryRelay) {
                if ($beneficiaryRelay->getCentre() === $beneficiary->getCentres()[$i]) {
                    $this->assertFalse($beneficiaryRelay->getBValid());
                }
            }
        }
    }

    /**
     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        $professional = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS)->object();
        $beneficiary = BeneficiaireFactory::createOne()->object();
        $url = sprintf($url, $beneficiary->getId());
        $relays = $professional->getCentres();
        $values = [
            'affiliate_beneficiary[relays][0]' => $relays[0]->getId(),
            'affiliate_beneficiary[relays][1]' => $relays[1]->getId(),
            'affiliate_beneficiary[relays][2]' => $relays[2]->getId(),
            'affiliate_beneficiary[secretAnswer]' => sprintf('%s-WRONG', $beneficiary->getReponseSecrete()),
        ];

        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        yield 'Should return an error when secret answer is not valid' => [
            self::URL,
            'affiliate_beneficiary_relays',
            'submit',
            self::FORM_VALUES,
            [
                [
                    'message' => 'wrong_secret_answer',
                ],
            ],
            MemberFixture::MEMBER_MAIL_WITH_RELAYS,
            'div.invalid-feedback',
        ];
    }

    /**
     * @dataProvider provideTestFormIsNotValidNoRelaysSelected
     */
    public function testFormIsNotValidNoRelaysSelected(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        $beneficiary = BeneficiaireFactory::createOne()->object();
        $url = sprintf($url, $beneficiary->getId());
        $values = [];

        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }

    public function provideTestFormIsNotValidNoRelaysSelected(): ?\Generator
    {
        yield 'Should return an error when user select 0 relays' => [
            self::URL,
            'affiliate_beneficiary_relays',
            'submit',
            self::FORM_VALUES,
            [
                [
                    'message' => 'beneficiary_affiliation_empty_relays',
                ],
            ],
            MemberFixture::MEMBER_MAIL_WITH_RELAYS,
            'div.invalid-feedback',
        ];
    }

    public function testInfoMessageIfNoRelayAvailable(): void
    {
        $professional = MembreFactory::createOne()->object();
        $beneficiary = BeneficiaireFactory::createOne()->object();

        $crawler = $this->assertRoute(
            sprintf(self::URL, $beneficiary->getId()),
            200,
            $professional->getUser()->getEmail(),
        )->getCrawler();

        self::assertTrue(str_contains($crawler->text(), self::$translator->trans('beneficiary_already_affiliated_to_all_relays')));
    }
}
