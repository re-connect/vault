<?php

namespace App\Tests\v2\API\v2\PasswordGrant\Document;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Document;
use App\Entity\SharedDocument;
use App\Manager\DocumentManager;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\API\v2\PasswordGrant\AbstractPasswordGrantApiV2TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class MobileDocumentTest extends AbstractPasswordGrantApiV2TestCase
{
    private const string PRESIGNED_URL = 'https://bucket.test/presigned';

    public function testUploadDocuments(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $documentsCount = count(DocumentFactory::findBy(['beneficiaire' => $beneficiary->getId()]));

        $client = static::createClient();
        $client->disableReboot();
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('putFile')->willReturn('object-key');
        $documentManager->method('getPresignedUrl')->willReturn(self::PRESIGNED_URL);
        self::getContainer()->set(DocumentManager::class, $documentManager);

        $this->loginAsUser($client, BeneficiaryFixture::BENEFICIARY_MAIL);
        // The mobile app uploads on the unversioned route
        $response = $client->request(
            Request::METHOD_POST,
            sprintf('/api/beneficiaries/%d/documents?access_token=%s', $beneficiary->getId(), $this->accessToken),
            ['extra' => ['files' => ['files' => [$this->getUploadedFile(), $this->getUploadedFile()]]]],
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertCount(2, $response->toArray());
        $this->assertJsonContains([['nom' => 'scan.pdf', 'b_prive' => true, 'beneficiaire_id' => $beneficiary->getId()]]);
        $this->assertCount($documentsCount + 2, DocumentFactory::findBy(['beneficiaire' => $beneficiary->getId()]));
    }

    public function testCanNotUploadWithoutFiles(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        $client = static::createClient();
        $this->loginAsUser($client, BeneficiaryFixture::BENEFICIARY_MAIL);
        $client->request(
            Request::METHOD_POST,
            sprintf('/api/beneficiaries/%d/documents?access_token=%s', $beneficiary->getId(), $this->accessToken),
            ['headers' => ['Content-Type' => 'multipart/form-data']],
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function provideSizes(): \Generator
    {
        yield 'Large' => ['large'];
        yield 'Thumbnails' => ['thumbnails'];
    }

    /** @dataProvider provideSizes */
    public function testShowDocumentSizeRedirectsToThumbnail(string $size): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $document = DocumentFactory::createOne([
            'beneficiaire' => $beneficiary,
            'extension' => 'jpg',
            'thumbnailKey' => 'thumbnail-key',
            'bPrive' => false,
        ]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_GET, sprintf('/documents/%d/%s', $document->getId(), $size));

        $this->assertResponseRedirects();
        $this->assertStringContainsString('thumbnail-key', self::getClient()->getResponse()->headers->get('Location'));
    }

    public function testMoveDocumentToFolder(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $document = DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);
        $folder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_PATCH, sprintf('/documents/%d/folder/%d', $document->getId(), $folder->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSame($folder->getId(), $this->findDocument($document->getId())->getDossier()?->getId());
    }

    public function testGetDocumentOutFromFolder(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $folder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);
        $document = DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false, 'dossier' => $folder]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_PATCH, sprintf('/documents/%d/get-out-from-folder', $document->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->findDocument($document->getId())->getDossier());
    }

    public function testShareDocument(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $document = DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_POST, sprintf('/documents/%d/share', $document->getId()), ['json' => [
            'email' => 'recipient@mail.com',
        ]]);

        $this->assertResponseStatusCodeSame(204);
        $this->assertEmailCount(1);
        $this->em->clear();
        $this->assertNotNull($this->em->getRepository(SharedDocument::class)->findOneBy(['document' => $document->getId()]));
    }

    public function testCanNotShareDocumentWithoutEmail(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $document = DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_POST, sprintf('/documents/%d/share', $document->getId()), ['json' => []]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testDeleteDocument(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $document = DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);
        $documentId = $document->getId();

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_DELETE, sprintf('/documents/%d', $documentId));

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull($this->findDocument($documentId));
    }

    private function findDocument(int $id): ?Document
    {
        $this->em->clear();

        return $this->em->find(Document::class, $id);
    }

    private function getUploadedFile(): UploadedFile
    {
        $path = sprintf('%s/test-file-%s.pdf', sys_get_temp_dir(), uniqid());
        copy(self::getContainer()->getParameter('kernel.project_dir').'/tests/test-file.pdf', $path);

        return new UploadedFile($path, 'scan.pdf', 'application/pdf', null, true);
    }
}
