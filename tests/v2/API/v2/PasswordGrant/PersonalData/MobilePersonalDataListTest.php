<?php

namespace App\Tests\v2\API\v2\PasswordGrant\PersonalData;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\API\v2\PasswordGrant\AbstractPasswordGrantApiV2TestCase;
use Symfony\Component\HttpFoundation\Request;

class MobilePersonalDataListTest extends AbstractPasswordGrantApiV2TestCase
{
    public function provideResources(): \Generator
    {
        yield 'Notes' => ['notes', NoteFactory::class];
        yield 'Contacts' => ['contacts', ContactFactory::class];
        yield 'Events' => ['events', EventFactory::class];
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testBeneficiaryListsAllPersonalData(string $resource, string $factory): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $expectedIds = array_map(fn ($entity) => $entity->getId(), $factory::findBy(['beneficiaire' => $beneficiary->getId()]));

        $response = $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_GET, sprintf('/beneficiaries/%d/%s', $beneficiary->getId(), $resource));

        $this->assertResponseStatusCodeSame(202);
        $this->assertNotEmpty($expectedIds);
        $this->assertEqualsCanonicalizing($expectedIds, array_column($response->toArray(), 'id'));
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testMemberListsOnlySharedPersonalData(string $resource, string $factory): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $expectedIds = array_map(fn ($entity) => $entity->getId(), $factory::findBy(['beneficiaire' => $beneficiary->getId(), 'bPrive' => false]));

        $response = $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_GET, sprintf('/beneficiaries/%d/%s', $beneficiary->getId(), $resource));

        $this->assertResponseStatusCodeSame(202);
        $this->assertNotEmpty($expectedIds);
        $this->assertEqualsCanonicalizing($expectedIds, array_column($response->toArray(), 'id'));
    }

    /** @dataProvider provideResources */
    public function testCanNotListPersonalDataOfAnotherBeneficiary(string $resource): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, Request::METHOD_GET, sprintf('/beneficiaries/%d/%s', $beneficiary->getId(), $resource));

        $this->assertResponseStatusCodeSame(403);
    }
}
