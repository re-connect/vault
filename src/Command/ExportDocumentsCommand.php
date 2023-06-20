<?php

namespace App\Command;

use App\Entity\Document;
use App\Service\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export-documents',
    description: 'Export all documents on a period',
)]
class ExportDocumentsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExportService $exportService,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('startDate', null, InputOption::VALUE_REQUIRED, 'Start date')
            ->addOption('endDate', null, InputOption::VALUE_REQUIRED, 'End date')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $startDate = date_create($input->getOption('startDate'));
        $endDate = date_create($input->getOption('endDate'));
        $startDateToString = $startDate->format('dmY');
        $endDateToString = $endDate->format('dmY');

        $data = [];

        foreach ($this->getDocumentsForPeriod($startDate, $endDate) as $document) {
            try {
                $creatorUsername = $document->getCreatorUser()?->getEntity()?->getUsername();
            } catch (EntityNotFoundException $e) {
                $io->info($e->getMessage());
                $creatorUsername = null;
            }

            $data[] = [
                $document->getNom(),
                $document->getBeneficiaire()?->getUsername(),
                $creatorUsername,
                $document->getCreatorClient()?->getEntity()?->getNom(),
                $document->getCreatedAt()->format('d/m/Y'),
            ];
        }

        $header = ['Nom du document', 'Bénéficiaire', 'Créé par', 'Créé par (client)', 'Date de création'];
        $title = sprintf('export-documents-%s-%s', $startDateToString, $endDateToString);
        $sheetIntro = sprintf('Export de documents sur la période du %s au %s', $startDateToString, $endDateToString);

        $this->exportService->saveFileToDisk(
            $this->exportService->exportDataToXlsx(
                $title,
                $sheetIntro,
                $header,
                $data,
            ),
            $io,
        );

        $io->success('Documents exported');

        return Command::SUCCESS;
    }

    /**
     * @return Document[]
     */
    private function getDocumentsForPeriod(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->em->createQueryBuilder()
            ->select('d')
            ->from(Document::class, 'd')
            ->where('d.createdAt between :startDate and :endDate')
            ->setParameters([
                'startDate' => $startDate,
                'endDate' => $endDate,
            ])
            ->orderBy('d.createdAt')
            ->getQuery()
            ->getResult();
    }
}
