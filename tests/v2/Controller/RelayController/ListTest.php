<?php

namespace App\Tests\v2\Controller\RelayController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\MembreCentre;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class ListTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/relays/mine';
    private const SHOW_BENEFICIARY_BUTTON = 'Voir les bénéficiaires';
    private const SHOW_PROFESSIONAL_BUTTON = 'Voir les professionnels';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code when authenticated as member' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
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

    public function testRelayCardButtonsNoPermission(): void
    {
        $html = $this->getProfessionalOnRelayList([]);
        self::assertStringNotContainsString(self::SHOW_BENEFICIARY_BUTTON, $html);
        self::assertStringNotContainsString(self::SHOW_PROFESSIONAL_BUTTON, $html);
    }

    public function testRelayCardButtonsShowBeneficiary(): void
    {
        $html = $this->getProfessionalOnRelayList([MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES => true]);
        self::assertStringContainsString(self::SHOW_BENEFICIARY_BUTTON, $html);
        self::assertStringNotContainsString(self::SHOW_PROFESSIONAL_BUTTON, $html);
    }

    public function testRelayCardButtonsShowProfessionals(): void
    {
        $html = $this->getProfessionalOnRelayList([MembreCentre::TYPEDROIT_GESTION_MEMBRES => true]);
        self::assertStringNotContainsString(self::SHOW_BENEFICIARY_BUTTON, $html);
        self::assertStringContainsString(self::SHOW_PROFESSIONAL_BUTTON, $html);
    }

    public function testRelayCardButtonsShowProfessionalsAndBeneficiaries(): void
    {
        $html = $this->getProfessionalOnRelayList([
            MembreCentre::TYPEDROIT_GESTION_MEMBRES => true,
            MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES => true,
        ]);
        self::assertStringContainsString(self::SHOW_BENEFICIARY_BUTTON, $html);
        self::assertStringContainsString(self::SHOW_PROFESSIONAL_BUTTON, $html);
    }

    public function getProfessionalOnRelayList(array $permissions): string
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $user = $this->getTestUserFromDb(MemberFixture::MEMBER_MAIL_WITH_UNIQUE_RELAY_SHARED_WITH_BENEFICIARIES);
        $client->loginUser($user);

        self::assertEquals(1, count($user->getSubjectMembre()->getMembresCentres()));
        $uniqueMembreCentre = $user->getSubjectMembre()->getMembresCentres()[0];
        $uniqueMembreCentre->setDroits($permissions);

        return $client->request('GET', self::URL)->html();
    }
}
