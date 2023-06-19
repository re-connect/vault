<?php

namespace App\Command;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        private readonly string $kernelProjectDir,
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

        $header = ['Nom du document', 'Bénéficiaire (id)', 'Créé par (user id)', 'Créé par (client)', 'Date de création'];
        $data = [];

        foreach ($this->getDocumentsForPeriod($startDate, $endDate) as $document) {
            $data[] = [
                $document->getNom(),
                $document->getBeneficiaireId(),
                $document->getCreatorUser()?->getEntity()?->getId(),
                $document->getCreatorClient()?->getEntity()?->getNom(),
                $document->getCreatedAt()->format('d/m/Y'),
            ];
        }

        $title = sprintf('export-documents-%s-%s', $startDateToString, $endDateToString);
        $spreadSheet = new Spreadsheet();
        $spreadSheet->getProperties()->setTitle($title);
        $sheet = $spreadSheet->getActiveSheet();
        $sheetIntro = [sprintf('Export de documents sur la période du %s au %s', $startDateToString, $endDateToString)];
        $sheet->fromArray([$sheetIntro, [], $header, ...$data]);
        $sheet->setAutoFilter(sprintf('A3:%s3', $sheet->getHighestColumn()));
        $filePath = sprintf('%s/var/export/%s.xlsx', $this->kernelProjectDir, $title);

        try {
            $io->info('Exporting documents...');
            (new Xlsx($spreadSheet))->save($filePath);
        } catch (\Exception) {
            $io->error('Error exporting documents');

            return Command::FAILURE;
        }

        $io->success(sprintf('You can find export at path %s', $filePath));

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
