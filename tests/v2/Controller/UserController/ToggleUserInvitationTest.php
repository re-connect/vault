<?php

namespace App\Tests\v2\Controller\UserController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\MembreCentre;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;

class ToggleUserInvitationTest extends AbstractControllerTest
{
    private const URL = '/user/%s/toggle-invite/%s';

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
        // Test toggle pro invite
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL)->object();
        $relay = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS)->object()->getCentres()[0];
        $url = sprintf(self::URL, $user->getId(), $relay->getId());

        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        // Test toggle beneficiary invite
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $url = sprintf(self::URL, $user->getId(), $relay->getId());

        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated on settings page' => [self::URL, 302, null, '/login'];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code when authenticated as member' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS];
    }

    /** @dataProvider provideTestForceBeneficiaryAffiliation */
    public function testForceBeneficiaryAffiliation(string $email, bool $isCreating)
    {
        $loggedUser = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS)->object();
        $relay = $loggedUser->getCentres()[0];
        $testedUser = UserFactory::findByEmail($email)->object();

        self::assertEquals($isCreating, $testedUser->getSubjectBeneficiaire()->getCreationProcess()->isCreating());
        self::assertFalse($testedUser->isLinkedToRelay($relay));

        $url = sprintf(self::URL, $testedUser->getId(), $relay->getId());
        $this->assertRoute($url, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS);

        $testedUser = UserFactory::find($testedUser)->object();
        $relay = RelayFactory::find($relay)->object();
        self::assertEquals($isCreating, $testedUser->getUserRelay($relay)->getBValid());
    }

    public function provideTestForceBeneficiaryAffiliation(): ?\Generator
    {
        yield 'Should not force acceptation for existing beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL, false];
        yield 'Should force acceptation for beneficiary in creation' => [BeneficiaryFixture::BENEFICIARY_MAIL_IN_CREATION, true];
    }

    public function testProPermissionOnInvitation(): void
    {
        $loggedUser = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS)->object();
        $relay = $loggedUser->getCentres()[0];
        $testedUser = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_NO_RELAY_NO_PERMISSION)->object();

        self::assertFalse($testedUser->isLinkedToRelay($relay));

        $url = sprintf(self::URL, $testedUser->getId(), $relay->getId());
        $this->assertRoute($url, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS);

        $testedUser = UserFactory::find($testedUser)->object();
        $relay = RelayFactory::find($relay)->object();
        $userRelay = $testedUser->getUserRelay($relay);

        self::assertSame(
            [
                MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES => true,
                MembreCentre::TYPEDROIT_GESTION_MEMBRES => false,
            ],
            $userRelay->getDroits()
        );
    }
}
