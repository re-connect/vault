<?php

namespace App\Tests\v2\API\v3\ClientCredentials\Document;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\v2\API\AbstractClientCredentialsApiTestCase;
use Reconnect\S3Bundle\Service\FlysystemS3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\ResponseInterface;

class UploadTest extends AbstractClientCredentialsApiTestCase
{
    public function provideClients(): \Generator
    {
        yield 'Should upload with documents create scope' => ['create_only_client', 201];
        yield 'Should not upload with read only scopes' => ['read_only_client', 403];
        yield 'Should not upload with no scopes' => ['no_scopes_client', 403];
    }

    /** @dataProvider provideClients */
    public function testUpload(string $clientName, int $expectedStatus): void
    {
        $this->upload($clientName, BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK)->getId());

        $this->assertResponseStatusCodeSame($expectedStatus);
    }

    public function testRosalieUploadsInItsFolder(): void
    {
        $beneficiaryId = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK)->getId();

        $response = $this->upload('rosalie', $beneficiaryId);

        $this->assertResponseStatusCodeSame(201);
        $document = DocumentFactory::find($response->toArray()['id']);
        $this->assertSame('rosalie', $document->getDossier()?->getNom());
    }

    private function upload(string $clientName, int $beneficiaryId): ResponseInterface
    {
        $client = static::createClient();
        $client->disableReboot();
        $s3Client = $this->createMock(FlysystemS3Client::class);
        $s3Client->method('uploadFile')->willReturn('object-key');
        $s3Client->method('generateThumbnail')->willReturn('thumbnail-key');
        $s3Client->method('getPresignedUrl')->willReturn('https://bucket.test/presigned');
        $client->getContainer()->set(FlysystemS3Client::class, $s3Client);
        $this->loginAsClient($client, $clientName);

        return $client->request(Request::METHOD_POST, $this->generateUrl('/documents'), [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['beneficiary_id' => $beneficiaryId],
                'files' => ['file' => $this->getPdf()],
            ],
        ]);
    }

    private function getPdf(): UploadedFile
    {
        $path = sprintf('%s/scan-%s.pdf', sys_get_temp_dir(), uniqid());
        copy(self::getContainer()->getParameter('kernel.project_dir').'/tests/test-file.pdf', $path);

        return new UploadedFile($path, 'scan.pdf', 'application/pdf', null, true);
    }
}
