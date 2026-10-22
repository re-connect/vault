<?php

namespace App\Tests\v2\Controller\UserController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Centre;
use App\Entity\MembreCentre;
use App\Tests\Factory\BeneficiaryRelayFactory;
use App\Tests\Factory\MembreCentreFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;

use function Zenstruck\Foundry\Persistence\refresh;

class ToggleUserInvitationTest extends AbstractControllerTest
{
    private const URL = '/user/%s/toggle-invite/%s';

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
        // Test toggle pro invite
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL);
        $relay = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS)->getCentres()[0];
        $url = sprintf(self::URL, $user->getId(), $relay->getId());

        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS === $userMail) {
            $expectedRedirect = sprintf('/user/%s/invite', $user->getId());
        }

        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        // Test toggle beneficiary invite
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $url = sprintf(self::URL, $user->getId(), $relay->getId());

        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS === $userMail) {
            $expectedRedirect = sprintf('/beneficiary/%s/affiliate/relays', $user->getSubject()->getId());
        }

        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated on settings page' => [self::URL, 302, null, '/login'];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code when authenticated as member' => [self::URL, 302, MemberFixture::MEMBER_MAIL_WITH_RELAYS];
    }

    /** @dataProvider provideTestForceBeneficiaryAffiliation */
    public function testForceBeneficiaryAffiliation(string $email, bool $isCreating)
    {
        $loggedUser = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS);
        $relay = $loggedUser->getCentres()[0];
        $testedUser = UserFactory::findByEmail($email);

        self::assertEquals($isCreating, $testedUser->getSubjectBeneficiaire()->getCreationProcess()->isCreating());
        self::assertFalse($testedUser->isLinkedToRelay($relay));

        $url = sprintf(self::URL, $testedUser->getId(), $relay->getId());
        $this->assertRoute($url, 302, MemberFixture::MEMBER_MAIL_WITH_RELAYS, sprintf('/beneficiary/%s/affiliate/relays', $testedUser->getSubject()->getId()));

        refresh($testedUser);
        refresh($relay);
        self::assertEquals($isCreating, $testedUser->getUserRelay($relay)->getBValid());
    }

    public function provideTestForceBeneficiaryAffiliation(): ?\Generator
    {
        yield 'Should not force acceptation for existing beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL, false];
        yield 'Should force acceptation for beneficiary in creation' => [BeneficiaryFixture::BENEFICIARY_MAIL_IN_CREATION, true];
    }

    public function testProPermissionOnInvitation(): void
    {
        $loggedUser = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS);
        $relay = $loggedUser->getCentres()[0];
        $testedUser = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_NO_RELAY_NO_PERMISSION);

        self::assertFalse($testedUser->isLinkedToRelay($relay));

        $url = sprintf(self::URL, $testedUser->getId(), $relay->getId());
        $this->assertRoute($url, 302, MemberFixture::MEMBER_MAIL_WITH_RELAYS, sprintf('/user/%s/invite', $testedUser->getId()));

        refresh($testedUser);
        refresh($relay);
        $userRelay = $testedUser->getUserRelay($relay);

        self::assertSame(
            [
                MembreCentre::MANAGE_BENEFICIARIES_PERMISSION => true,
                MembreCentre::MANAGE_PROS_PERMISSION => false,
            ],
            $userRelay->getDroits()
        );
    }

    public function testCannotInviteBeneficiaryToUnaffiliatedRelay(): void
    {
        $this->assertInvitationDenied(BeneficiaryFixture::BENEFICIARY_MAIL, RelayFactory::createOne());
    }

    public function testCannotForceBeneficiaryAffiliationToUnaffiliatedRelay(): void
    {
        $this->assertInvitationDenied(BeneficiaryFixture::BENEFICIARY_MAIL_IN_CREATION, RelayFactory::createOne());
    }

    public function testCannotInviteBeneficiaryToPendingRelay(): void
    {
        $relay = $this->createLoggedProRelay(false, true, true);

        $this->assertInvitationDenied(BeneficiaryFixture::BENEFICIARY_MAIL, $relay);
    }

    public function testCannotInviteProWithoutProManagementPermission(): void
    {
        $relay = $this->createLoggedProRelay(true, true, false);

        $this->assertInvitationDenied(MemberFixture::MEMBER_MAIL_NO_RELAY_NO_PERMISSION, $relay);
    }

    public function testCannotRemoveBeneficiaryWithValidatedAffiliation(): void
    {
        $relay = $this->createLoggedProRelay(true, true, true);
        $testedUser = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        BeneficiaryRelayFactory::createOne(['beneficiaire' => $testedUser->getSubjectBeneficiaire(), 'centre' => $relay, 'bValid' => true]);

        $this->assertRoute(sprintf(self::URL, $testedUser->getId(), $relay->getId()), 403, MemberFixture::MEMBER_MAIL_WITH_RELAYS);

        refresh($testedUser);
        refresh($relay);
        self::assertTrue($testedUser->hasValidLinkToRelay($relay));
    }

    private function assertInvitationDenied(string $testedUserMail, Centre $relay): void
    {
        $testedUser = UserFactory::findByEmail($testedUserMail);

        $this->assertRoute(sprintf(self::URL, $testedUser->getId(), $relay->getId()), 403, MemberFixture::MEMBER_MAIL_WITH_RELAYS);

        refresh($testedUser);
        refresh($relay);
        self::assertFalse($testedUser->isLinkedToRelay($relay));
    }

    private function createLoggedProRelay(bool $isValid, bool $beneficiaryManagement, bool $proManagement): Centre
    {
        $relay = RelayFactory::createOne();
        MembreCentreFactory::createOne([
            'membre' => MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS),
            'centre' => $relay,
            'bValid' => $isValid,
            'droits' => [
                MembreCentre::MANAGE_BENEFICIARIES_PERMISSION => $beneficiaryManagement,
                MembreCentre::MANAGE_PROS_PERMISSION => $proManagement,
            ],
        ]);

        return $relay;
    }
}
