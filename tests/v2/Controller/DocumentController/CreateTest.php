<?php

namespace App\Tests\v2\Controller\DocumentController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Reconnect\S3Bundle\Service\FlysystemS3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CreateTest extends AbstractControllerTest
{
    private const URL = '/beneficiary/%s/documents/upload';

    public function provideTestUploadRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null];
        yield 'Should return 201 status code when authenticated as beneficiaire' => [self::URL, 201, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 201 status code when authenticated as member with relay in common' => [self::URL, 201, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
    }

    /** @dataProvider provideTestUploadRoute */
    public function testUpload(string $url, int $expectedStatusCode, ?string $userMail = null): void
    {
        self::ensureKernelShutdown();
        $client = $this->createClient();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $url = sprintf($url, $beneficiary->getId());
        $file = new UploadedFile('tests/test-file.pdf', 'test', 'application/pdf');

        if ($userMail) {
            $user = $this->getTestUserFromDb($userMail);
            $client->loginUser($user);
        }

        $client->request('POST', $url, [], ['files' => [$file]]);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public function provideTestCanUploadIntoFolder(): \Generator
    {
        yield 'Beneficiary can upload into their own folder' => [BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Pro can upload into a shared folder of a managed beneficiary' => [MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
    }

    /** @dataProvider provideTestCanUploadIntoFolder */
    public function testCanUploadIntoFolder(string $userMail): void
    {
        self::ensureKernelShutdown();
        $client = $this->createClient();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $folder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);
        $file = new UploadedFile('tests/test-file.pdf', 'test', 'application/pdf');
        $client->loginUser($this->getTestUserFromDb($userMail));

        $s3Client = $this->createMock(FlysystemS3Client::class);
        $s3Client->method('uploadFile')->willReturn('object-key');
        $s3Client->method('generateThumbnail')->willReturn('thumbnail-key');
        $s3Client->method('getPresignedUrl')->willReturn('https://bucket.test/presigned');
        $client->getContainer()->set(FlysystemS3Client::class, $s3Client);

        $client->request('POST', sprintf(self::URL.'?folder=%d', $beneficiary->getId(), $folder->getId()), [], ['files' => [$file]]);

        $this->assertResponseStatusCodeSame(201);
        self::assertCount(1, DocumentFactory::findBy(['dossier' => $folder->getId()]));
    }

    public function provideTestCanNotUploadIntoForbiddenFolder(): \Generator
    {
        yield 'Beneficiary can not upload into another beneficiary folder' => [BeneficiaryFixture::BENEFICIARY_MAIL, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, false];
        yield 'Pro can not upload into the folder of another managed beneficiary' => [MemberFixture::MEMBER_WITH_CLIENT, BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK, false];
        yield 'Pro can not upload into a private folder' => [MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, BeneficiaryFixture::BENEFICIARY_MAIL, true];
    }

    /** @dataProvider provideTestCanNotUploadIntoForbiddenFolder */
    public function testCanNotUploadIntoForbiddenFolder(string $userMail, string $folderOwnerMail, bool $isPrivateFolder): void
    {
        self::ensureKernelShutdown();
        $client = $this->createClient();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $folder = FolderFactory::createOne([
            'beneficiaire' => BeneficiaireFactory::findByEmail($folderOwnerMail),
            'bPrive' => $isPrivateFolder,
        ]);
        $file = new UploadedFile('tests/test-file.pdf', 'test', 'application/pdf');
        $client->loginUser($this->getTestUserFromDb($userMail));

        $client->request('POST', sprintf(self::URL.'?folder=%d', $beneficiary->getId(), $folder->getId()), [], ['files' => [$file]]);

        $this->assertResponseStatusCodeSame(403);
        self::assertCount(0, DocumentFactory::findBy(['dossier' => $folder->getId()]));
    }

    public function testCanNotUploadIntoUnknownFolder(): void
    {
        self::ensureKernelShutdown();
        $client = $this->createClient();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $documentsCount = DocumentFactory::count(['beneficiaire' => $beneficiary]);
        $file = new UploadedFile('tests/test-file.pdf', 'test', 'application/pdf');
        $client->loginUser($this->getTestUserFromDb(BeneficiaryFixture::BENEFICIARY_MAIL));

        $client->request('POST', sprintf(self::URL.'?folder=%d', $beneficiary->getId(), 999999999), [], ['files' => [$file]]);

        $this->assertResponseStatusCodeSame(400);
        self::assertSame($documentsCount, DocumentFactory::count(['beneficiaire' => $beneficiary]));
    }
}
