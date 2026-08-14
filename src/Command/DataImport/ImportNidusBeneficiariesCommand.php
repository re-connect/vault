<?php

namespace App\Command\DataImport;

use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\User;
use App\ManagerV2\DocumentManager;
use App\ManagerV2\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(
    name: 'app:import-nidus-beneficiary',
    description: 'Import nidus beneficiary, with personnal documents',
)]
/**
 * Nidus folder should be store in '/var/nidus_import/'.
 */
class ImportNidusBeneficiariesCommand extends Command
{
    /** @var array<int, array{file: string, location: string, reason: string}> */
    private array $ignoredDocuments = [];

    private int $importedDocumentsCount = 0;

    public function __construct(
        private readonly string $kernelProjectDir,
        private readonly UserManager $userManager,
        private readonly EntityManagerInterface $em,
        private readonly DocumentManager $documentManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('folderName', InputArgument::REQUIRED, 'Input folder name');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $folderPath = sprintf('%s/var/nidus_import/%s', $this->kernelProjectDir, $input->getArgument('folderName'));

        $beneficiary = $this->createBeneficiary(
            $this->getBeneficiaryRecord(sprintf('%s/user-profile.csv', $folderPath))
        );

        $this->importFolderAndDocumentsRecursively(
            $beneficiary,
            sprintf('%s/documents', $folderPath),
        );

        $this->em->flush();

        $this->displaySummary($io, $beneficiary);

        return Command::SUCCESS;
    }

    private function getBeneficiaryRecord(string $beneficiaryFilePath): array
    {
        $csv = Reader::createFromPath($beneficiaryFilePath)->setHeaderOffset(0);

        $records = (new Statement())->process($csv);

        foreach ($records as $record) {
            return $record;
        }

        throw new \RuntimeException('Le fichier CSV est vide ou invalide.');
    }

    private function createBeneficiary(array $record): Beneficiaire
    {
        $user = new User();
        $user
            ->setPlainPassword($this->userManager->getRandomPassword(100))
            ->setPrenom($record['name'])
            ->setNom($record['lastName'])
            ->setBActif(true)
            ->setTypeUser(User::USER_TYPE_BENEFICIAIRE)
            ->setTelephone('' !== $record['phone'] ? $record['phone'] : null)
            ->setEmail('' !== $record['email'] ? $record['email'] : null);

        $beneficiary = new Beneficiaire();
        $beneficiary
            ->setUser($user)
            ->setDateNaissance(new \DateTime($record['birthdate']) ?? null);

        $this->em->persist($user);
        $this->em->flush();

        return $beneficiary;
    }

    private function importFolderAndDocumentsRecursively(Beneficiaire $beneficiary, string $folderPath, ?Dossier $parentFolder = null): void
    {
        $finder = (new Finder())->in($folderPath)->depth(0);

        foreach ($finder as $item) {
            if ($item->isDir()) {
                $folder = new Dossier();
                $folder
                    ->setNom($item->getFilename())
                    ->setDossierParent($parentFolder)
                    ->setBeneficiaire($beneficiary)
                    ->setBPrive(true);

                $this->em->persist($folder);
                $this->importFolderAndDocumentsRecursively($beneficiary, $item->getRealPath(), $folder);
            } else {
                $uploadedFile = new UploadedFile(
                    $item->getRealPath(),
                    $item->getFilename(),
                    mime_content_type($item->getRealPath()),
                    null,
                    true
                );

                if (!in_array($uploadedFile->guessExtension(), Document::ALLOWED_FILE_EXTENSIONS, true)) {
                    $this->ignoredDocuments[] = [
                        'file' => $item->getFilename(),
                        'location' => $parentFolder?->getNom() ?? '/',
                        'reason' => 'Type de fichier non supporté',
                    ];

                    continue;
                }

                $document = $this->documentManager->uploadFile($uploadedFile, $beneficiary, $parentFolder);

                if (null === $document) {
                    $this->ignoredDocuments[] = [
                        'file' => $item->getFilename(),
                        'location' => $parentFolder?->getNom() ?? '/',
                        'reason' => "Échec de l'upload",
                    ];

                    continue;
                }

                $document->setBPrive(true);
                ++$this->importedDocumentsCount;
            }
        }
    }

    private function displaySummary(SymfonyStyle $io, Beneficiaire $beneficiary): void
    {
        $io->success(sprintf(
            'Import réussi pour %s %s : %d document(s) importé(s).',
            $beneficiary->getUser()->getPrenom(),
            $beneficiary->getUser()->getNom(),
            $this->importedDocumentsCount,
        ));

        if ([] === $this->ignoredDocuments) {
            return;
        }

        $io->warning(sprintf('%d document(s) ignoré(s) car non pris en charge :', count($this->ignoredDocuments)));
        $io->table(
            ['Fichier', 'Emplacement', 'Raison'],
            array_map(
                static fn (array $ignored) => [$ignored['file'], $ignored['location'], $ignored['reason']],
                $this->ignoredDocuments,
            ),
        );
    }
}
