<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Center;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\RelayFixture;
use App\Entity\BeneficiaireCentre;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\BeneficiaryRelayFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class RelayLinkTest extends AbstractPasswordGrantApiTestCase
{
    public function testBeneficiaryAcceptsInvitation(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $relay = RelayFactory::createOne();
        $userRelay = BeneficiaryRelayFactory::createOne(['beneficiaire' => $beneficiary, 'centre' => $relay, 'bValid' => false]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_PATCH, sprintf('/centers/%d/accept', $relay->getId()));

        $this->assertResponseIsSuccessful();
        $this->em->clear();
        $this->assertTrue($this->em->find(BeneficiaireCentre::class, $userRelay->getId())->getBValid());
    }

    public function testBeneficiaryLeavesRelay(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $relay = RelayFactory::find(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_2]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_PATCH, sprintf('/centers/%d/leave', $relay->getId()));

        $this->assertResponseIsSuccessful();
        $this->em->clear();
        $this->assertNull($this->em->getRepository(BeneficiaireCentre::class)->findOneBy(['beneficiaire' => $beneficiary->getId(), 'centre' => $relay->getId()]));
    }

    public function provideActions(): \Generator
    {
        yield 'Accept' => ['accept'];
        yield 'Leave' => ['leave'];
    }

    /** @dataProvider provideActions */
    public function testReturnsNotFoundWhenNotLinkedToRelay(string $action): void
    {
        $relay = RelayFactory::find(['nom' => RelayFixture::DEFAULT_PRO_RELAY]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_PATCH, sprintf('/centers/%d/%s', $relay->getId(), $action));

        $this->assertResponseStatusCodeSame(404);
    }
}
