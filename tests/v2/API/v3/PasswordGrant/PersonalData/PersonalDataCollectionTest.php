<?php

namespace App\Tests\v2\API\v3\PasswordGrant\PersonalData;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Beneficiaire;
use App\Entity\DonneePersonnelle;
use App\Entity\Evenement;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class PersonalDataCollectionTest extends AbstractPasswordGrantApiTestCase
{
    public function provideResources(): \Generator
    {
        yield 'Documents' => ['/documents', DocumentFactory::class];
        yield 'Folders' => ['/folders', FolderFactory::class];
        yield 'Notes' => ['/notes', NoteFactory::class];
        yield 'Contacts' => ['/contacts', ContactFactory::class];
        yield 'Events' => ['/events', EventFactory::class];
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testBeneficiaryListsOwnPersonalData(string $endpoint, string $factory): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $this->createSharedAndPrivateItems($factory, $beneficiary);
        $expectedIds = $this->getIds($factory::findBy(['beneficiaire' => $beneficiary->getId()]));

        $ids = $this->getCollectionIds(BeneficiaryFixture::BENEFICIARY_MAIL, $endpoint);

        $this->assertNotEmpty($expectedIds);
        $this->assertEqualsCanonicalizing($expectedIds, $ids);
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testMemberListsSharedPersonalDataOfManagedBeneficiaries(string $endpoint, string $factory): void
    {
        $managedBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $notManagedBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);
        $this->createSharedAndPrivateItems($factory, $managedBeneficiary);
        $sharedIds = $this->getIds($factory::findBy(['beneficiaire' => $managedBeneficiary->getId(), 'bPrive' => false]));
        $privateIds = $this->getIds($factory::findBy(['beneficiaire' => $managedBeneficiary->getId(), 'bPrive' => true]));
        $notManagedIds = $this->getIds($factory::findBy(['beneficiaire' => $notManagedBeneficiary->getId()]));

        $ids = $this->getCollectionIds(MemberFixture::MEMBER_WITH_CLIENT, $endpoint);

        $this->assertNotEmpty($sharedIds);
        $this->assertEmpty(array_diff($sharedIds, $ids), 'Shared personal data of a managed beneficiary should be listed');
        $this->assertEmpty(array_intersect($privateIds, $ids), 'Private personal data should not be listed');
        $this->assertEmpty(array_intersect($notManagedIds, $ids), 'Personal data of a beneficiary the pro does not manage should not be listed');
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testBeneficiaryListsOwnPersonalDataFromBeneficiaryEndpoint(string $endpoint, string $factory): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $this->createSharedAndPrivateItems($factory, $beneficiary);
        $expectedIds = $this->getIds($factory::findBy(['beneficiaire' => $beneficiary->getId()]));

        $ids = $this->getCollectionIds(BeneficiaryFixture::BENEFICIARY_MAIL, sprintf('/beneficiaries/%d%s', $beneficiary->getId(), $endpoint));

        $this->assertEqualsCanonicalizing($expectedIds, $ids);
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testMemberListsSharedPersonalDataFromBeneficiaryEndpoint(string $endpoint, string $factory): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $this->createSharedAndPrivateItems($factory, $beneficiary);
        $expectedIds = $this->getIds($factory::findBy(['beneficiaire' => $beneficiary->getId(), 'bPrive' => false]));

        $ids = $this->getCollectionIds(MemberFixture::MEMBER_WITH_CLIENT, sprintf('/beneficiaries/%d%s', $beneficiary->getId(), $endpoint));

        $this->assertEqualsCanonicalizing($expectedIds, $ids);
    }

    public function provideForbiddenBeneficiaryEndpoints(): \Generator
    {
        foreach (['/documents', '/folders', '/notes', '/contacts', '/events'] as $endpoint) {
            yield sprintf('%s: another beneficiary', $endpoint) => [$endpoint, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
            yield sprintf('%s: a pro who does not manage the beneficiary', $endpoint) => [$endpoint, MemberFixture::MEMBER_MAIL_WITH_RELAYS];
        }
    }

    /** @dataProvider provideForbiddenBeneficiaryEndpoints */
    public function testCanNotListPersonalDataOfAnUnmanagedBeneficiary(string $endpoint, string $email): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        $this->requestAsUser($email, Request::METHOD_GET, sprintf('/beneficiaries/%d%s', $beneficiary->getId(), $endpoint));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListingPersonalDataOfAnUnknownBeneficiaryReturnsNotFound(): void
    {
        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_GET, '/beneficiaries/999999999/notes');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPastEventsAreNotListed(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $pastEvent = EventFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false, 'date' => new \DateTime('-1 day')]);
        $upcomingEvent = EventFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false, 'date' => new \DateTime('+1 day')]);

        $ids = $this->getCollectionIds(BeneficiaryFixture::BENEFICIARY_MAIL, '/events');

        $this->assertContains($upcomingEvent->getId(), $ids);
        $this->assertNotContains($pastEvent->getId(), $ids);
    }

    /**
     * Fixture events may already be past, so each test creates its own upcoming items.
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    private function createSharedAndPrivateItems(string $factory, Beneficiaire $beneficiary): void
    {
        $attributes = EventFactory::class === $factory ? ['date' => new \DateTime('+1 day')] : [];
        $factory::createOne([...$attributes, 'beneficiaire' => $beneficiary, 'bPrive' => false]);
        $factory::createOne([...$attributes, 'beneficiaire' => $beneficiary, 'bPrive' => true]);
    }

    /** @return int[] */
    private function getCollectionIds(string $email, string $endpoint): array
    {
        $response = $this->requestAsUser($email, Request::METHOD_GET, $endpoint);
        $this->assertResponseIsSuccessful();

        return array_column($response->toArray()['hydra:member'], 'id');
    }

    /**
     * Events are only listed when in the future.
     *
     * @param DonneePersonnelle[] $entities
     **/
    private function getIds(array $entities): array
    {
        $entities = array_filter($entities, fn (DonneePersonnelle $entity) => !$entity instanceof Evenement || $entity->getDate() > new \DateTime());

        return array_values(array_map(fn (DonneePersonnelle $entity) => $entity->getId(), $entities));
    }
}
