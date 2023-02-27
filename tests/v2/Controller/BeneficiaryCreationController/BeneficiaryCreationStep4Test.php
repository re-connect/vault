<?php

namespace App\Tests\v2\Controller\BeneficiaryCreationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class BeneficiaryCreationStep4Test extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/beneficiary/create/4/%s';
    private const FORM_VALUES = [
        'create_beneficiary[relays][0]' => '',
    ];

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate(['isCreating' => true, 'remotely' => false])->object();
        $url = sprintf($url, $creationProcess->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as professional' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
    }

    /**
     * @param array<string, string> $values
     *
     * @dataProvider provideTestFormIsValid
     */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $professional = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS)->object();
        $relays = $professional->getCentres();
        $values = [
            'create_beneficiary[relays][0]' => $relays[0]->getId(),
            'create_beneficiary[relays][1]' => $relays[1]->getId(),
            'create_beneficiary[relays][2]' => $relays[2]->getId(),
        ];

        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate(['isCreating' => true, 'remotely' => false])->object();
        $url = sprintf($url, $creationProcess->getId());
        $redirectUrl = sprintf($redirectUrl, $creationProcess->getId());
        $beneficiary = $creationProcess->getBeneficiary();
        // Check form valid
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
        // Check that beneficiary is linked with 3 relays
        $this->assertCount(3, $beneficiary->getCentres());
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals($relays[$i]->getId(), $beneficiary->getCentres()[$i]->getId());
        }
        // Check that creator relay is first selected relay
        $beneficiary = BeneficiaireFactory::find($beneficiary);
        self::assertEquals($relays[0]->getId(), $beneficiary->getUser()->getCreatorCentre()->getEntity()->getId());
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should redirect to step 5 when form is correct' => [
            self::URL,
            'confirm',
            self::FORM_VALUES,
            MemberFixture::MEMBER_MAIL_WITH_RELAYS,
            '/beneficiary/create/5/%s',
        ];
    }
}
