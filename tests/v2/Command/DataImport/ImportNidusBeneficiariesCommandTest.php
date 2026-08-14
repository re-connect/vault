<?php

namespace App\Tests\v2\Command\DataImport;

use App\Command\DataImport\ImportNidusBeneficiariesCommand;
use App\Entity\Document;
use App\ManagerV2\DocumentManager;
use App\ManagerV2\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ImportNidusBeneficiariesCommandTest extends TestCase
{
    private string $projectDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->projectDir = sys_get_temp_dir().'/nidus_import_test_'.uniqid();

        $documentsDir = $this->projectDir.'/var/nidus_import/fixture/documents';
        $this->filesystem->mkdir($documentsDir);
        $this->filesystem->dumpFile(
            $this->projectDir.'/var/nidus_import/fixture/user-profile.csv',
            "name,lastName,phone,email,birthdate\nJean,Dupont,,,1980-01-01\n",
        );
        $this->filesystem->dumpFile($documentsDir.'/passport.pdf', '%PDF-1.4 fake content');
        $this->filesystem->dumpFile($documentsDir.'/malware.exe', random_bytes(64));
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectDir);
    }

    public function testUnsupportedDocumentTypeIsIgnoredAndReportedInSummary(): void
    {
        $userManager = $this->createMock(UserManager::class);
        $userManager->method('getRandomPassword')->willReturn('random-password');

        $em = $this->createMock(EntityManagerInterface::class);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects(self::once())
            ->method('uploadFile')
            ->with(self::callback(fn ($file) => 'passport.pdf' === $file->getClientOriginalName()))
            ->willReturn((new Document())->setBPrive(false));

        $command = new ImportNidusBeneficiariesCommand($this->projectDir, $userManager, $em, $documentManager);

        $application = new Application();
        $application->addCommand($command);
        $commandTester = new CommandTester($application->find('app:import-nidus-beneficiary'));
        $commandTester->execute(['folderName' => 'fixture']);

        self::assertSame(0, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('1 document(s) importé(s)', $output);
        self::assertStringContainsString('1 document(s) ignoré(s)', $output);
        self::assertStringContainsString('malware.exe', $output);
        self::assertStringContainsString('Type de fichier non supporté', $output);
    }

    public function testAllDocumentsSupportedProducesNoIgnoredSummary(): void
    {
        $this->filesystem->remove($this->projectDir.'/var/nidus_import/fixture/documents/malware.exe');

        $userManager = $this->createMock(UserManager::class);
        $userManager->method('getRandomPassword')->willReturn('random-password');

        $em = $this->createMock(EntityManagerInterface::class);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects(self::once())
            ->method('uploadFile')
            ->willReturn((new Document())->setBPrive(false));

        $command = new ImportNidusBeneficiariesCommand($this->projectDir, $userManager, $em, $documentManager);

        $application = new Application();
        $application->addCommand($command);
        $commandTester = new CommandTester($application->find('app:import-nidus-beneficiary'));
        $commandTester->execute(['folderName' => 'fixture']);

        self::assertSame(0, $commandTester->getStatusCode());
        self::assertStringNotContainsString('ignoré', $commandTester->getDisplay());
    }

    public function testBeneficiaryEntityIsBuiltFromCsvRecord(): void
    {
        $userManager = $this->createMock(UserManager::class);
        $userManager->method('getRandomPassword')->willReturn('random-password');

        $em = $this->createMock(EntityManagerInterface::class);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('uploadFile')->willReturn((new Document())->setBPrive(false));

        $command = new ImportNidusBeneficiariesCommand($this->projectDir, $userManager, $em, $documentManager);

        $application = new Application();
        $application->addCommand($command);
        $commandTester = new CommandTester($application->find('app:import-nidus-beneficiary'));
        $commandTester->execute(['folderName' => 'fixture']);

        self::assertStringContainsString('Jean Dupont', $commandTester->getDisplay());
    }
}
