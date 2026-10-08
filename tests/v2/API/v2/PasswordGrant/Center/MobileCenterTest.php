<?php

namespace App\Tests\v2\API\v2\PasswordGrant\Center;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\DataFixtures\v2\RelayFixture;
use App\Entity\BeneficiaireCentre;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\BeneficiaryRelayFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\v2\API\v2\PasswordGrant\AbstractPasswordGrantApiV2TestCase;
use Symfony\Component\HttpFoundation\Request;

class MobileCenterTest extends AbstractPasswordGrantApiV2TestCase
{
    public function testBeneficiaryListsCenters(): void
    {
        $response = $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_GET, '/centers');

        $this->assertResponseStatusCodeSame(202);
        $centerIds = array_map(fn (array $userCenter) => $userCenter['centre']['id'], $response->toArray());
        $this->assertEqualsCanonicalizing([
            RelayFactory::find(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1])->getId(),
            RelayFactory::find(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_2])->getId(),
        ], $centerIds);
        $this->assertTrue($response->toArray()[0]['b_valid']);
    }

    public function testMemberListsCenters(): void
    {
        $response = $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_GET, '/centers');

        $this->assertResponseStatusCodeSame(202);
        $centerIds = array_map(fn (array $userCenter) => $userCenter['centre']['id'], $response->toArray());
        $this->assertSame([RelayFactory::find(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1])->getId()], $centerIds);
    }

    public function testListShowsPendingInvitations(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $pendingRelay = RelayFactory::createOne();
        BeneficiaryRelayFactory::createOne(['beneficiaire' => $beneficiary, 'centre' => $pendingRelay, 'bValid' => false]);

        $response = $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_GET, '/centers');

        $this->assertResponseStatusCodeSame(202);
        $pendingInvitations = array_values(array_filter($response->toArray(), fn (array $userCenter) => false === $userCenter['b_valid']));
        $this->assertCount(1, $pendingInvitations);
        $this->assertSame($pendingRelay->getId(), $pendingInvitations[0]['centre']['id']);
    }

    public function testBeneficiaryAcceptsInvitation(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $pendingRelay = RelayFactory::createOne();
        $userRelay = BeneficiaryRelayFactory::createOne(['beneficiaire' => $beneficiary, 'centre' => $pendingRelay, 'bValid' => false]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_PATCH, sprintf('/centers/%d/accept', $pendingRelay->getId()));

        $this->assertResponseIsSuccessful();
        $this->em->clear();
        $this->assertTrue($this->em->find(BeneficiaireCentre::class, $userRelay->getId())->getBValid());
    }

    public function testBeneficiaryLeavesCenter(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $relay = RelayFactory::find(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_2]);

        $this->requestAsUser(
            BeneficiaryFixture::BENEFICIARY_MAIL,
            Request::METHOD_PATCH,
            sprintf('/users/%d/centers/%d/leave', $beneficiary->getUser()->getId(), $relay->getId()),
        );

        $this->assertResponseStatusCodeSame(204);
        $this->em->clear();
        $this->assertNull($this->em->getRepository(BeneficiaireCentre::class)->findOneBy(['beneficiaire' => $beneficiary->getId(), 'centre' => $relay->getId()]));
    }
}
