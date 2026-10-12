<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Document;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Document;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Reconnect\S3Bundle\Service\FlysystemS3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\ResponseInterface;

class UploadTest extends AbstractPasswordGrantApiTestCase
{
    public function testBeneficiaryUploadsPrivateDocumentForThemself(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $otherBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $response = $this->upload(BeneficiaryFixture::BENEFICIARY_MAIL, ['beneficiary_id' => $otherBeneficiary->getId()]);

        $this->assertResponseStatusCodeSame(201);
        $document = $this->findDocument($response);
        $this->assertSame($beneficiary->getId(), $document->getBeneficiaire()->getId());
        $this->assertTrue($document->getBPrive());
        $this->assertNull($document->getDossier());
    }

    public function testBeneficiaryUploadsIntoOwnFolder(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $folder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);

        $response = $this->upload(BeneficiaryFixture::BENEFICIARY_MAIL, ['folder_id' => $folder->getId()]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($folder->getId(), $this->findDocument($response)->getDossier()?->getId());
    }

    public function testProUploadsSharedDocumentForManagedBeneficiary(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        $response = $this->upload(MemberFixture::MEMBER_WITH_CLIENT, ['beneficiary_id' => $beneficiary->getId()]);

        $this->assertResponseStatusCodeSame(201);
        $document = $this->findDocument($response);
        $this->assertSame($beneficiary->getId(), $document->getBeneficiaire()->getId());
        $this->assertFalse($document->getBPrive());
    }

    public function testProMustGiveBeneficiary(): void
    {
        $this->upload(MemberFixture::MEMBER_WITH_CLIENT, []);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testProCanNotUploadForUnmanagedBeneficiary(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $this->upload(MemberFixture::MEMBER_WITH_CLIENT, ['beneficiary_id' => $beneficiary->getId()]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testProCanNotUploadIntoPrivateFolder(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $privateFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => true]);

        $this->upload(MemberFixture::MEMBER_WITH_CLIENT, ['beneficiary_id' => $beneficiary->getId(), 'folder_id' => $privateFolder->getId()]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRejectsUnsupportedFile(): void
    {
        $path = sprintf('%s/malware-%s.exe', sys_get_temp_dir(), uniqid());
        file_put_contents($path, random_bytes(64));

        $this->upload(BeneficiaryFixture::BENEFICIARY_MAIL, [], new UploadedFile($path, 'malware.exe', null, null, true));

        $this->assertResponseStatusCodeSame(400);
    }

    /** @param array<string, int> $parameters */
    private function upload(string $email, array $parameters, ?UploadedFile $file = null): ResponseInterface
    {
        $client = static::createClient();
        $client->disableReboot();
        $this->mockS3Client($client);
        $this->loginAsUser($client, $email);

        return $client->request(Request::METHOD_POST, $this->generateUrl('/documents'), [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => $parameters,
                'files' => ['file' => $file ?? $this->getPdf()],
            ],
        ]);
    }

    private function mockS3Client(Client $client): void
    {
        $s3Client = $this->createMock(FlysystemS3Client::class);
        $s3Client->method('uploadFile')->willReturn('object-key');
        $s3Client->method('generateThumbnail')->willReturn('thumbnail-key');
        $s3Client->method('getPresignedUrl')->willReturn('https://bucket.test/presigned');
        $client->getContainer()->set(FlysystemS3Client::class, $s3Client);
    }

    private function getPdf(): UploadedFile
    {
        $path = sprintf('%s/scan-%s.pdf', sys_get_temp_dir(), uniqid());
        copy(self::getContainer()->getParameter('kernel.project_dir').'/tests/test-file.pdf', $path);

        return new UploadedFile($path, 'scan.pdf', 'application/pdf', null, true);
    }

    private function findDocument(ResponseInterface $response): Document
    {
        $this->em->clear();

        return $this->em->find(Document::class, $response->toArray()['id']);
    }
}
