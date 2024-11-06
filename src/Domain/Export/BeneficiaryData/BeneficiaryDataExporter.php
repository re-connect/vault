<?php

namespace App\Domain\Export\BeneficiaryData;

use App\Entity\Beneficiaire;
use App\Entity\Contact;
use App\Entity\Evenement;
use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;

readonly class BeneficiaryDataExporter
{
    public function __construct(private EntityManagerInterface $em, private LoggerInterface $logger)
    {
    }

    private const array EXPORTED_ENTITIES_CSV = [Contact::class, Note::class, Evenement::class];
    private const array EXPORTED_PROPERTIES = [
        'nom',
        'bPrive',
        'createdAt',
        'contenu',
        'date',
        'lieu',
        'commentaire',
        'prenom',
        'telephone',
        'email',
    ];

    public function export(Beneficiaire $beneficiary): StreamedResponse
    {
        $zipName = sprintf('beneficiary_data_%d.zip', $beneficiary->getId());

        return new StreamedResponse(
            fn () => $this->createZip($zipName, $beneficiary),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    $zipName,
                ),
            ]
        );
    }

    public function createZip(string $zipName, Beneficiaire $beneficiary): void
    {
        $zip = new ZipStream(outputName: $zipName);

        try {
            $this->exportBeneficiaryData($zip, $beneficiary);
            $zip->finish();
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error during beneficiary data export, cause : %s', $e->getMessage()));
        }
    }

    /**
     * @throws \Exception
     */
    private function exportBeneficiaryData(ZipStream $zip, Beneficiaire $beneficiary): void
    {
        foreach (self::EXPORTED_ENTITIES_CSV as $exportedEntity) {
            $this->exportEntityToCsv($zip, $beneficiary, $exportedEntity);
        }
    }

    /**
     * @param class-string<object> $entityClass
     *
     * @throws \Exception
     */
    public function exportEntityToCsv(ZipStream $zip, Beneficiaire $beneficiary, string $entityClass): void
    {
        $reflectionClass = new \ReflectionClass($entityClass);
        $exportedProperties = array_filter(
            $reflectionClass->getProperties(),
            fn (\ReflectionProperty $property) => in_array($property->getName(), self::EXPORTED_PROPERTIES),
        );

        $handle = fopen('php://temp', 'w+');
        $writer = Writer::createFromStream($handle);

        $header = array_map(fn (\ReflectionProperty $property) => $property->getName(), $exportedProperties);
        $writer->insertOne($header);
        $entities = $this->em->getRepository($entityClass)->findBy(['beneficiaire' => $beneficiary]);

        foreach ($entities as $entity) {
            $data = [];
            foreach ($exportedProperties as $property) {
                $data[] = $this->getStringValue($property->getValue($entity));
            }
            $writer->insertOne($data);
        }

        rewind($handle);
        $zip->addFileFromStream(sprintf('%s.csv', $reflectionClass->getShortName()), $handle);
        fclose($handle);
    }

    private function getStringValue(mixed $propertyValue): string
    {
        if ($propertyValue instanceof \DateTime) {
            return $propertyValue->format('d/m/Y');
        }

        if (is_bool($propertyValue)) {
            return $propertyValue ? 'True' : 'False';
        }

        return (string) $propertyValue;
    }
}
