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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    private EntityManagerInterface $em;
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

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function saveExport(ExportModel $exportModel): StreamedResponse
    {
        return $this->getStreamedResponse($this->createExportSpreadSheet($exportModel, self::EXPORT_COLUMNS, $this->getExportSheetData($exportModel)));
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

    private function createExportSpreadSheet(ExportModel $exportModel, $exportSheetHeader, $exportSheetData): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheetIntro = $this->getSheetIntro($exportModel);
        $sheet->fromArray([$sheetIntro, [], $exportSheetHeader, ...$exportSheetData], null, 'A1', true);
        for ($i = 'A'; $i <= $sheet->getHighestColumn(); ++$i) {
            $sheet->getColumnDimension($i)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private function getSheetIntro(ExportModel $exportModel): array
    {
        return [
            'Éléments créés',
            sprintf(
                'du %s au %s',
                $exportModel->getStartDate()->format('d-m-Y'),
                $exportModel->getEndDate()->format('d-m-Y')
            ),
        ];
    }

    private function getStreamedResponse(Spreadsheet $spreadsheet): StreamedResponse
    {
        $fileName = sprintf('export_cfn_%s.xlsx', (new \DateTime())->format('d-m-Y'));
        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
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
