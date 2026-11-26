<?php

namespace App\Tests\v2\API\v3\PasswordGrant\PersonalData;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\DonneePersonnelle;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use App\Tests\v2\BucketServiceSpyTrait;
use Symfony\Component\HttpFoundation\Request;

class DeleteTest extends AbstractPasswordGrantApiTestCase
{
    use BucketServiceSpyTrait;

    private const array RESOURCES = [
        'Document' => ['/documents', DocumentFactory::class],
        'Folder' => ['/folders', FolderFactory::class],
        'Note' => ['/notes', NoteFactory::class],
        'Contact' => ['/contacts', ContactFactory::class],
        'Event' => ['/events', EventFactory::class],
    ];

    public function provideAllowedDeletions(): \Generator
    {
        foreach (self::RESOURCES as $name => [$endpoint, $factory]) {
            yield sprintf('%s: beneficiary deletes own private item', $name) => [$endpoint, $factory, BeneficiaryFixture::BENEFICIARY_MAIL, true];
        }
        foreach (['Note', 'Contact', 'Event'] as $name) {
            yield sprintf('%s: pro deletes shared item', $name) => [...self::RESOURCES[$name], MemberFixture::MEMBER_WITH_CLIENT, false];
        }
    }

    /**
     * @dataProvider provideAllowedDeletions
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testDelete(string $endpoint, string $factory, string $email, bool $isPrivate): void
    {
        $item = $this->createItem($factory, $isPrivate);
        $itemId = $item->getId();

        $this->requestAsUser($email, Request::METHOD_DELETE, sprintf('%s/%d', $endpoint, $itemId));

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull($this->findItem($factory, $itemId));
    }

    public function provideForbiddenDeletions(): \Generator
    {
        foreach (['Document', 'Folder'] as $name) {
            yield sprintf('%s: pro can not delete shared item', $name) => [...self::RESOURCES[$name], MemberFixture::MEMBER_WITH_CLIENT, false];
        }
        foreach (['Note', 'Contact', 'Event'] as $name) {
            yield sprintf('%s: pro can not delete private item', $name) => [...self::RESOURCES[$name], MemberFixture::MEMBER_WITH_CLIENT, true];
        }
        foreach (self::RESOURCES as $name => [$endpoint, $factory]) {
            yield sprintf('%s: another beneficiary can not delete item', $name) => [$endpoint, $factory, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, false];
        }
    }

    /**
     * @dataProvider provideForbiddenDeletions
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testCanNotDelete(string $endpoint, string $factory, string $email, bool $isPrivate): void
    {
        $item = $this->createItem($factory, $isPrivate);

        $this->requestAsUser($email, Request::METHOD_DELETE, sprintf('%s/%d', $endpoint, $item->getId()));

        $this->assertResponseStatusCodeSame(403);
        $this->assertNotNull($this->findItem($factory, $item->getId()));
    }

    public function testDeletingDocumentRemovesItsFilesFromStorage(): void
    {
        $document = DocumentFactory::createOne([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
            'objectKey' => 'deleted-document-object-key',
            'thumbnailKey' => 'deleted-document-thumbnail-key',
        ]);
        $client = static::createClient();
        $deletedKeys = $this->spyOnDeletedFiles($client);
        $this->loginAsUser($client, BeneficiaryFixture::BENEFICIARY_MAIL);

        $client->request(Request::METHOD_DELETE, $this->generateUrl(sprintf('/documents/%d', $document->getId())));

        $this->assertResponseStatusCodeSame(204);
        $this->assertEqualsCanonicalizing(['deleted-document-object-key', 'deleted-document-thumbnail-key'], $deletedKeys->getArrayCopy());
    }

    public function testDeletingFolderRemovesFilesOfAllItsDocuments(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $folder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'dossierParent' => null]);
        $subFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'dossierParent' => $folder]);
        DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'dossier' => $folder, 'objectKey' => 'folder-object-key', 'thumbnailKey' => 'folder-thumbnail-key']);
        DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'dossier' => $subFolder, 'objectKey' => 'sub-folder-object-key', 'thumbnailKey' => 'sub-folder-thumbnail-key']);
        $client = static::createClient();
        $deletedKeys = $this->spyOnDeletedFiles($client);
        $this->loginAsUser($client, BeneficiaryFixture::BENEFICIARY_MAIL);

        $client->request(Request::METHOD_DELETE, $this->generateUrl(sprintf('/folders/%d', $folder->getId())));

        $this->assertResponseStatusCodeSame(204);
        $this->assertEqualsCanonicalizing(
            ['folder-object-key', 'folder-thumbnail-key', 'sub-folder-object-key', 'sub-folder-thumbnail-key'],
            $deletedKeys->getArrayCopy(),
        );
    }

    /** @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory */
    private function createItem(string $factory, bool $isPrivate): DonneePersonnelle
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        return $factory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => $isPrivate]);
    }

    /** @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory */
    private function findItem(string $factory, int $id): ?DonneePersonnelle
    {
        $this->em->clear();

        return $this->em->find($factory::class(), $id);
    }
}
