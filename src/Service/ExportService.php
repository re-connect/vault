<?php

namespace App\Service;

use App\Entity\Beneficiaire;
use App\Entity\Contact;
use App\Entity\Document;
use App\Entity\Evenement;
use App\Entity\Note;
use App\Form\Model\ExportModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    private const EXPORT_COLUMNS = [
        'Filtres',
        'Bénéficiaires',
        'Bénéficiaires avec email',
        'Bénéficiaires avec téléphone',
        'Notes',
        'Événements',
        'Contacts',
        'Documents',
        'Documents provenants de x CFN',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $kernelProjectDir,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function saveExport(ExportModel $exportModel): StreamedResponse
    {
        return $this->getStreamedResponse(
            $this->exportDataToXlsx(
                sprintf('export_cfn_%s.xlsx', (new \DateTime())->format('d-m-Y')),
                $this->getSheetIntro($exportModel),
                self::EXPORT_COLUMNS,
                $this->getExportSheetData($exportModel),
            )
        );
    }

    private function getExportSheetData(ExportModel $exportModel): array
    {
        return $exportModel->hasFilters()
            ? $exportModel->getFiltersCollection()->map($this->getExportSheetFiltersCount($exportModel))->toArray()
            : [$this->getExportSheetCount($exportModel)];
    }

    private function getExportSheetFiltersCount(ExportModel $exportModel): \Closure
    {
        return function ($filter) use ($exportModel) {
            return $this->getExportSheetCount($exportModel, $filter);
        };
    }

    private function getExportSheetCount(ExportModel $exportModel, $filter = null): array
    {
        return [
            (string) $filter ?? '',
            $this->getCount($this->getBeneficiariesCountBaseQb(), $exportModel, $filter),
            $this->getCount($this->getBeneficiariesCountBaseQb()->andWhere('u.email IS NOT NULL'), $exportModel, $filter),
            $this->getCount($this->getBeneficiariesCountBaseQb()->andWhere('u.telephone IS NOT NULL'), $exportModel, $filter),
            $this->getCount($this->getItemsCountBaseQb(Note::class), $exportModel, $filter),
            $this->getCount($this->getItemsCountBaseQb(Evenement::class), $exportModel, $filter),
            $this->getCount($this->getItemsCountBaseQb(Contact::class), $exportModel, $filter),
            $this->getCount($this->getItemsCountBaseQb(Document::class), $exportModel, $filter),
            $this->getCount($this->getItemsBaseQb(Document::class)->groupBy('b'), $exportModel, $filter),
        ];
    }

    private function getCount(QueryBuilder $qb, ExportModel $exportModel, $filter): int
    {
        $parameters = [
            'startDate' => $exportModel->getStartDate(),
            'endDate' => $exportModel->getEndDate(),
        ];
        $this->addFiltersToQb($exportModel, $qb, $parameters, $filter);
        $qb->setParameters($parameters);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Exception $e) {
            return count($qb->getQuery()->getResult());
        }
    }

    private function getBeneficiariesCountBaseQb(): QueryBuilder
    {
        return $this->em->getRepository(Beneficiaire::class)
            ->createQueryBuilder('b')
            ->select('COUNT(b)')
            ->innerJoin('b.user', 'u')
            ->andWhere('u.test != true')
            ->andWhere('b.createdAt > :startDate')
            ->andWhere('b.createdAt < :endDate')
            ->groupBy('b');
    }

    private function getItemsBaseQb(string $item): QueryBuilder
    {
        return $this->em
            ->createQueryBuilder()
            ->from($item, 'i')
            ->select('i')
            ->innerJoin('i.beneficiaire', 'b')
            ->innerJoin('b.user', 'u')
            ->andWhere('u.test != true')
            ->andWhere('i.createdAt > :startDate')
            ->andWhere('i.createdAt < :endDate');
    }

    private function getItemsCountBaseQb(string $item): QueryBuilder
    {
        return $this->em
            ->createQueryBuilder()
            ->from($item, 'i')
            ->select('COUNT(i)')
            ->innerJoin('i.beneficiaire', 'b')
            ->innerJoin('b.user', 'u')
            ->andWhere('u.test != true')
            ->andWhere('i.createdAt > :startDate')
            ->andWhere('i.createdAt < :endDate');
    }

    public function exportDataToXlsx(string $title, string $intro, array $header, array $data): Xlsx
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle($title);
        $sheet = $spreadsheet->getActiveSheet();
        $sheetIntro = [$intro];
        $sheet->fromArray([$sheetIntro, [], $header, ...$data]);
        $sheet->setAutoFilter(sprintf('A3:%s3', $sheet->getHighestColumn()));
        for ($i = 'A'; $i <= $sheet->getHighestColumn(); ++$i) {
            $sheet->getColumnDimension($i)->setAutoSize(true);
        }

        return new Xlsx($spreadsheet);
    }

    private function getSheetIntro(ExportModel $exportModel): string
    {
        return sprintf(
            'Éléments créés du %s au %s',
            $exportModel->getStartDate()->format('d-m-Y'),
            $exportModel->getEndDate()->format('d-m-Y')
        );
    }

    private function getStreamedResponse(Xlsx $xlsx): StreamedResponse
    {
        $fileName = $xlsx->getSpreadsheet()->getProperties()->getTitle();
        $response = new StreamedResponse(
            function () use ($xlsx) {
                $xlsx->save('php://output');
            }
        );
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    public function saveFileToDisk(Xlsx $xlsx, SymfonyStyle $io = null): void
    {
        $filePath = sprintf('%s/var/export/%s.xlsx', $this->kernelProjectDir, $xlsx->getSpreadsheet()->getProperties()->getTitle());

        try {
            $xlsx->save($filePath);
        } catch (\Exception $exception) {
            $errorMessage = sprintf('Error saving file %s to disk : %s', $filePath, $exception->getMessage());
            $io ? $io->error($errorMessage) : $this->logger->error($errorMessage);
        }
    }

    private function addFiltersToQb(ExportModel $exportModel, QueryBuilder $queryBuilder, array &$parameters, $filter): void
    {
        if ($filter) {
            $queryBuilder->innerJoin('b.beneficiairesCentres', 'bc')->andWhere('bc.bValid = true');
            if ($exportModel->hasCenterFilter()) {
                $queryBuilder->andWhere('bc.centre = :centre');
                $parameters['centre'] = $filter;
            } else {
                $queryBuilder->innerJoin('bc.centre', 'c')->andWhere('c.region = :region');
                $parameters['region'] = $filter;
            }
        }
    }
}
