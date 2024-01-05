<?php

namespace App\Tests\v2\Controller\RosalieInteroperabilityController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\Controller\AbstractControllerTest;

class AddSiSiaoNumberTest extends AbstractControllerTest
{
    private const URL = '/beneficiaries/%s/add-si-siao-number';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [302, null, '/login'];
        yield 'Should throw the 403 page' => [403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return a 200 status on get when authenticated as pro' => [200, MemberFixture::MEMBER_MAIL];
    }

    /** @dataProvider provideTestRoute */
    public function testRoute(int $expectedStatusCode, string $userMail = null, ?string $expectedRedirect = '', string $method = 'GET'): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();

        $this->assertRoute($this->buildUrlString(self::URL, [$beneficiary->getId()]), $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function testTemplateContainsTextAndForm(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();

        $client = $this->assertRoute($this->buildUrlString(self::URL, [$beneficiary->getId()]), 200, MemberFixture::MEMBER_MAIL);

        $this->assertSelectorTextContains('h1', 'Ajouter numéro SI-SIAO');
        $this->assertSelectorTextContains('h4', $beneficiary->getUsername());

        $client->submitForm('Ajouter', ['form[number]' => 'XXXXXXXX']);
        $this->assertResponseRedirects(sprintf('/beneficiary/%s/affiliate/relays', $beneficiary->getId()));
    }
}
