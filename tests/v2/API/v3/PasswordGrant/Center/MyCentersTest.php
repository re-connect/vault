<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Center;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\DataFixtures\v2\RelayFixture;
use App\Entity\MembreCentre;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\BeneficiaryRelayFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class MyCentersTest extends AbstractPasswordGrantApiTestCase
{
    public function testBeneficiaryListsRelaysIncludingPendingInvitations(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $pendingRelay = RelayFactory::createOne(['nom' => 'Centre qui invite']);
        BeneficiaryRelayFactory::createOne(['beneficiaire' => $beneficiary, 'centre' => $pendingRelay, 'bValid' => false]);

        $relays = $this->getMyRelays(BeneficiaryFixture::BENEFICIARY_MAIL);

        $this->assertResponseIsSuccessful();
        $this->assertEqualsCanonicalizing([
            [RelayFactory::find(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1])->getId(), true],
            [RelayFactory::find(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_2])->getId(), true],
            [$pendingRelay->getId(), false],
        ], array_map(fn (array $relay) => [$relay['centre']['id'], $relay['b_valid']], $relays));
        $pendingInvitation = array_values(array_filter($relays, fn (array $relay) => false === $relay['b_valid']))[0];
        $this->assertSame('Centre qui invite', $pendingInvitation['centre']['nom']);
        $this->assertArrayNotHasKey('droits', $pendingInvitation);
    }

    public function testMemberListsRelaysWithPermissions(): void
    {
        $relays = $this->getMyRelays(MemberFixture::MEMBER_WITH_CLIENT);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $relays);
        $this->assertSame(RelayFactory::find(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1])->getId(), $relays[0]['centre']['id']);
        $this->assertTrue($relays[0]['b_valid']);
        $this->assertTrue($relays[0]['droits'][MembreCentre::MANAGE_BENEFICIARIES_PERMISSION]);
    }

    /** @return array<array<string, mixed>> */
    private function getMyRelays(string $email): array
    {
        return $this->requestAsUser($email, Request::METHOD_GET, '/users/me/centers')->toArray();
    }
}
