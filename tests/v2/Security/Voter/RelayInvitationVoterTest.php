<?php

namespace App\Tests\v2\Security\Voter;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Centre;
use App\Entity\MembreCentre;
use App\Security\VoterV2\RelayInvitation;
use App\Security\VoterV2\RelayInvitationVoter;
use App\Tests\Factory\BeneficiaryRelayFactory;
use App\Tests\Factory\MembreCentreFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\AuthenticatedKernelTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Zenstruck\Foundry\Test\Factories;

class RelayInvitationVoterTest extends AuthenticatedKernelTestCase
{
    use Factories;

    /** @dataProvider provideProRelayRights */
    public function testProInvitationRequiresProManagementPermission(bool $proManagement, bool $expected): void
    {
        $relay = $this->createLoggedProRelay(true, $proManagement);

        self::assertSame($expected, $this->isGranted(MemberFixture::MEMBER_MAIL_NO_RELAY_NO_PERMISSION, $relay));
    }

    public function provideProRelayRights(): \Generator
    {
        yield 'Should grant with pro management permission' => [true, true];
        yield 'Should deny without pro management permission' => [false, false];
    }

    /** @dataProvider provideBeneficiaryRelays */
    public function testBeneficiaryInvitationRequiresValidRelay(?bool $isLoggedProAffiliationValid, bool $expected): void
    {
        $relay = null === $isLoggedProAffiliationValid
            ? RelayFactory::createOne()
            : $this->createLoggedProRelay($isLoggedProAffiliationValid, true);

        self::assertSame($expected, $this->isGranted(BeneficiaryFixture::BENEFICIARY_MAIL, $relay));
    }

    public function provideBeneficiaryRelays(): \Generator
    {
        yield 'Should grant on a relay where pro affiliation is valid' => [true, true];
        yield 'Should deny on a relay where pro affiliation is pending' => [false, false];
        yield 'Should deny on a relay the pro is not affiliated to' => [null, false];
    }

    /** @dataProvider provideValidatedBeneficiaries */
    public function testValidatedLinkCanOnlyBeToggledForBeneficiaryInCreation(string $beneficiaryMail, bool $expected): void
    {
        $relay = $this->createLoggedProRelay(true, true);
        BeneficiaryRelayFactory::createOne([
            'beneficiaire' => UserFactory::findByEmail($beneficiaryMail)->getSubjectBeneficiaire(),
            'centre' => $relay,
            'bValid' => true,
        ]);

        self::assertSame($expected, $this->isGranted($beneficiaryMail, $relay));
    }

    public function provideValidatedBeneficiaries(): \Generator
    {
        yield 'Should deny for an existing beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL, false];
        yield 'Should grant for a beneficiary in creation' => [BeneficiaryFixture::BENEFICIARY_MAIL_IN_CREATION, true];
    }

    public function testDenyWhenNotAuthenticated(): void
    {
        $relay = $this->createLoggedProRelay(true, true);
        $invitation = new RelayInvitation(UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL), $relay);

        self::assertFalse(self::getContainer()->get(AuthorizationCheckerInterface::class)->isGranted(RelayInvitationVoter::TOGGLE, $invitation));
    }

    private function isGranted(string $testedUserMail, Centre $relay): bool
    {
        $this->loginUser(MemberFixture::MEMBER_MAIL_WITH_RELAYS);
        $invitation = new RelayInvitation(UserFactory::findByEmail($testedUserMail), $relay);

        return self::getContainer()->get(AuthorizationCheckerInterface::class)->isGranted(RelayInvitationVoter::TOGGLE, $invitation);
    }

    private function createLoggedProRelay(bool $isValid, bool $proManagement): Centre
    {
        $relay = RelayFactory::createOne();
        MembreCentreFactory::createOne([
            'membre' => MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS),
            'centre' => $relay,
            'bValid' => $isValid,
            'droits' => [
                MembreCentre::MANAGE_BENEFICIARIES_PERMISSION => true,
                MembreCentre::MANAGE_PROS_PERMISSION => $proManagement,
            ],
        ]);

        return $relay;
    }
}
