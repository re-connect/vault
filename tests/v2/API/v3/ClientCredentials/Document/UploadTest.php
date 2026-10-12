<?php

namespace App\Tests\v2\API\v3\ClientCredentials\Document;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\API\AbstractClientCredentialsApiTestCase;
use Reconnect\S3Bundle\Service\FlysystemS3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

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
        $client = static::createClient();
        $client->disableReboot();
        $s3Client = $this->createMock(FlysystemS3Client::class);
        $s3Client->method('uploadFile')->willReturn('object-key');
        $s3Client->method('generateThumbnail')->willReturn('thumbnail-key');
        $s3Client->method('getPresignedUrl')->willReturn('https://bucket.test/presigned');
        $client->getContainer()->set(FlysystemS3Client::class, $s3Client);
        $this->loginAsClient($client, $clientName);
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK);

        $client->request(Request::METHOD_POST, $this->generateUrl('/documents'), [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['beneficiary_id' => $beneficiary->getId()],
                'files' => ['file' => $this->getPdf()],
            ],
        ]);

        $this->assertResponseStatusCodeSame($expectedStatus);
    }

    private function getPdf(): UploadedFile
    {
        $path = sprintf('%s/scan-%s.pdf', sys_get_temp_dir(), uniqid());
        copy(self::getContainer()->getParameter('kernel.project_dir').'/tests/test-file.pdf', $path);

        return new UploadedFile($path, 'scan.pdf', 'application/pdf', null, true);
    }
}
