<?php

namespace App\Tests\v2\Manager\DocumentManager;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\ManagerV2\DocumentManager;
use App\Repository\DocumentRepository;
use App\ServiceV2\BucketService;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\AuthenticatedKernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Reconnect\S3Bundle\Service\FlysystemS3Client;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateDocumentFromFileTest extends AuthenticatedKernelTestCase
{
    private ?DocumentManager $manager;
    private Security|MockObject $securityMock;

    protected function setUp(): void
    {
        parent::setUp();
        $s3ClientMock = $this->createMock(FlysystemS3Client::class);
        $repositoryMock = $this->createMock(DocumentRepository::class);
        $emMock = $this->createMock(EntityManagerInterface::class);
        $this->securityMock = $this->createMock(Security::class);
        $requestStackMock = $this->createMock(RequestStack::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $bucketServiceMock = $this->createMock(BucketService::class);
        $this->manager = new DocumentManager(
            $s3ClientMock,
            $repositoryMock,
            $emMock,
            $this->securityMock,
            $loggerMock,
            $requestStackMock,
            $translatorMock,
            $bucketServiceMock,
        );
    }

    public function provideTestUploadVisibilityNoFolder(): ?\Generator
    {
        yield 'Document should be private in root folder' => [
            BeneficiaryFixture::BENEFICIARY_MAIL,
            true,
        ];
        yield 'Document should be always shared for pro' => [
            MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
            false,
        ];
    }

    /** @dataProvider provideTestUploadVisibilityNoFolder */
    public function testUploadVisibilityNoFolder(
        string $userMail,
        bool $visibility,
    ): void {
        self::ensureKernelShutdown();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $this->loginUser($userMail);

        $file = $this->getDummyFile();
        $this->securityMock->method('getUser')->willReturn($this->getTestUserFromDb($userMail));
        $document = $this->getPrivateMethod(DocumentManager::class, 'createDocumentFromFile')->invokeArgs(
            $this->manager,
            [
                $file,
                Uuid::v4(),
                $file->getBasename(),
                $beneficiary,
                null,
            ]
        );

        self::assertEquals($visibility, $document->getBPrive());
    }

    public function provideTestUploadVisibilitySharedFolder(): ?\Generator
    {
        yield 'Document should be shared in shared folder' => [
            BeneficiaryFixture::BENEFICIARY_MAIL,
            false,
        ];
        yield 'Document should be always shared for pro' => [
            MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
            false,
        ];
    }

    /** @dataProvider provideTestUploadVisibilitySharedFolder */
    public function testUploadVisibilitySharedFolder(
        string $userMail,
        bool $visibility,
    ): void {
        self::ensureKernelShutdown();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $this->loginUser($userMail);

        $sharedFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false])->object();
        $file = $this->getDummyFile();
        $this->securityMock->method('getUser')->willReturn($this->getTestUserFromDb($userMail));
        $document = $this->getPrivateMethod(DocumentManager::class, 'createDocumentFromFile')->invokeArgs(
            $this->manager,
            [
                $file, Uuid::v4(),
                $file->getBasename(),
                $beneficiary,
                $sharedFolder,
            ],
        );

        self::assertEquals($visibility, $document->getBPrive());
    }

    public function testUploadVisibilityPrivateFolder(): void
    {
        self::ensureKernelShutdown();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $this->loginUser(BeneficiaryFixture::BENEFICIARY_MAIL);

        $privateFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => true])->object();
        $file = $this->getDummyFile();
        $this->securityMock->method('getUser')->willReturn($this->getTestUserFromDb(BeneficiaryFixture::BENEFICIARY_MAIL));
        $document = $this->getPrivateMethod(DocumentManager::class, 'createDocumentFromFile')->invokeArgs(
            $this->manager,
            [
                $file, Uuid::v4(),
                $file->getBasename(),
                $beneficiary,
                $privateFolder,
            ],
        );

        self::assertEquals(true, $document->getBPrive());
    }

    private function getDummyFile(): File
    {
        return new File('tests/test-file.pdf');
    }
}
