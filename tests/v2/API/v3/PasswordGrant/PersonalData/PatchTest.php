<?php

namespace App\Tests\v2\API\v3\PasswordGrant\PersonalData;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Document;
use App\Entity\DonneePersonnelle;
use App\Entity\Dossier;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class PatchTest extends AbstractPasswordGrantApiTestCase
{
    public function provideResources(): \Generator
    {
        yield 'Document' => ['/documents', DocumentFactory::class];
        yield 'Folder' => ['/folders', FolderFactory::class];
        yield 'Note' => ['/notes', NoteFactory::class];
        yield 'Contact' => ['/contacts', ContactFactory::class];
        yield 'Event' => ['/events', EventFactory::class];
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testBeneficiaryRenamesItem(string $endpoint, string $factory): void
    {
        $item = $this->createItem($factory, BeneficiaryFixture::BENEFICIARY_MAIL);

        $this->patch(BeneficiaryFixture::BENEFICIARY_MAIL, $endpoint, $item, ['nom' => 'Renamed']);

        $this->assertResponseIsSuccessful();
        // Document names are sanitized and suffixed with the file extension by DocumentListener
        $this->assertStringStartsWith('Renamed', $this->findItem($factory, $item->getId())->getNom());
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testBeneficiaryCanNotGiveItemToAnotherBeneficiary(string $endpoint, string $factory): void
    {
        $item = $this->createItem($factory, BeneficiaryFixture::BENEFICIARY_MAIL);
        $otherBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $this->patch(BeneficiaryFixture::BENEFICIARY_MAIL, $endpoint, $item, ['beneficiaire' => sprintf('/api/v3/beneficiaries/%d', $otherBeneficiary->getId())]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertBeneficiaryIs(BeneficiaryFixture::BENEFICIARY_MAIL, $factory, $item->getId());
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testProCanNotMoveItemBetweenManagedBeneficiaries(string $endpoint, string $factory): void
    {
        $item = $this->createItem($factory, BeneficiaryFixture::BENEFICIARY_MAIL);
        $otherManagedBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK);

        $this->patch(MemberFixture::MEMBER_WITH_CLIENT, $endpoint, $item, ['beneficiaire' => sprintf('/api/v3/beneficiaries/%d', $otherManagedBeneficiary->getId())]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertBeneficiaryIs(BeneficiaryFixture::BENEFICIARY_MAIL, $factory, $item->getId());
    }

    public function testBeneficiaryMovesDocumentIntoOwnFolder(): void
    {
        $document = $this->createItem(DocumentFactory::class, BeneficiaryFixture::BENEFICIARY_MAIL);
        $folder = $this->createItem(FolderFactory::class, BeneficiaryFixture::BENEFICIARY_MAIL);

        $this->patch(BeneficiaryFixture::BENEFICIARY_MAIL, '/documents', $document, ['dossier' => sprintf('/api/v3/folders/%d', $folder->getId())]);

        $this->assertResponseIsSuccessful();
        /** @var Document $document */
        $document = $this->findItem(DocumentFactory::class, $document->getId());
        $this->assertSame($folder->getId(), $document->getDossier()?->getId());
    }

    public function testCanNotMoveDocumentIntoAnotherBeneficiaryFolder(): void
    {
        $document = $this->createItem(DocumentFactory::class, BeneficiaryFixture::BENEFICIARY_MAIL);
        $otherFolder = $this->createItem(FolderFactory::class, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $this->patch(BeneficiaryFixture::BENEFICIARY_MAIL, '/documents', $document, ['dossier' => sprintf('/api/v3/folders/%d', $otherFolder->getId())]);

        $this->assertResponseStatusCodeSame(403);
        /** @var Document $document */
        $document = $this->findItem(DocumentFactory::class, $document->getId());
        $this->assertNull($document->getDossier());
    }

    public function testCanNotMoveFolderIntoAnotherBeneficiaryFolder(): void
    {
        $folder = $this->createItem(FolderFactory::class, BeneficiaryFixture::BENEFICIARY_MAIL);
        $otherFolder = $this->createItem(FolderFactory::class, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $this->patch(BeneficiaryFixture::BENEFICIARY_MAIL, '/folders', $folder, ['dossier_parent' => sprintf('/api/v3/folders/%d', $otherFolder->getId())]);

        $this->assertResponseStatusCodeSame(403);
        /** @var Dossier $folder */
        $folder = $this->findItem(FolderFactory::class, $folder->getId());
        $this->assertNull($folder->getDossierParent());
    }

    /** @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory */
    private function createItem(string $factory, string $beneficiaryEmail): DonneePersonnelle
    {
        return $factory::createOne(['beneficiaire' => BeneficiaireFactory::findByEmail($beneficiaryEmail), 'bPrive' => false]);
    }

    /** @param array<string, mixed> $body */
    private function patch(string $email, string $endpoint, DonneePersonnelle $item, array $body): void
    {
        $this->requestAsUser($email, Request::METHOD_PATCH, sprintf('%s/%d', $endpoint, $item->getId()), [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $body,
        ]);
    }

    /** @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory */
    private function assertBeneficiaryIs(string $beneficiaryEmail, string $factory, int $itemId): void
    {
        $this->assertSame(
            BeneficiaireFactory::findByEmail($beneficiaryEmail)->getId(),
            $this->findItem($factory, $itemId)->getBeneficiaire()->getId(),
        );
    }

    /** @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory */
    private function findItem(string $factory, int $id): DonneePersonnelle
    {
        $this->em->clear();

        return $this->em->find($factory::class(), $id);
    }
}
