<?php

namespace App\Domain\Download;

use App\Entity\Document;
use App\Entity\Dossier;
use App\ServiceV2\BucketService;
use App\ServiceV2\Traits\UserAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\Exception\OverflowException;
use ZipStream\ZipStream;

readonly class FolderTreeDownloader
{
    use UserAwareTrait;

    public function __construct(private BucketService $bucketService, private Security $security, private LoggerInterface $logger)
    {
    }

    public function downloadZip(Dossier $folder): ?StreamedResponse
    {
        return 0 === $folder->getDocuments()->count()
            ? null
            : new StreamedResponse(
                fn () => $this->createZip($folder),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => HeaderUtils::makeDisposition(
                        HeaderUtils::DISPOSITION_ATTACHMENT,
                        sprintf('%s.zip', $folder->getNom()),
                    ),
                ]
            );
    }

    private function createZip(Dossier $folder): void
    {
        $zip = new ZipStream(outputName: sprintf('%s.zip', $folder->getNom()));

        $this->addFolderTreeRecursively($zip, $folder);

        try {
            $zip->finish();
        } catch (OverflowException $e) {
            $this->logger->error(sprintf(
                'Error during zip download for folder %d from beneficiary %d, cause %s',
                $folder->getId(),
                $folder->getBeneficiaire()->getId(),
                $e->getMessage(),
            ));
        }
    }

    private function addFolderTreeRecursively(ZipStream $zip, Dossier $folder, string $folderPath = ''): void
    {
        $documents = $folder->getDocuments($this->getUser() === $folder->getBeneficiaire()->getUser())
            ->filter(fn (Document $document) => is_resource($this->bucketService->getObjectStream($document->getObjectKey())));

        foreach ($documents as $document) {
            $documentName = $this->sanitizeNode($document->getNom());
            $objectStream = $this->bucketService->getObjectStream($document->getObjectKey());
            if ($objectStream) {
                $zip->addFileFromStream(
                    !$folder->hasParentFolder()
                        ? $documentName
                        : sprintf('%s/%s', $folderPath, $documentName),
                    $objectStream,
                );
            }
        }

        $subFolders = $folder->getSousDossiers()->filter(fn (Dossier $folder) => !$folder->isPrivate() || $this->getUser() === $folder->getBeneficiaire()->getUser());

        foreach ($subFolders as $folder) {
            $this->addFolderTreeRecursively($zip, $folder, sprintf('%s/%s', $folderPath, $this->sanitizeNode($folder->getNom())));
        }
    }

    private function sanitizeNode(string $nodeName): string
    {
        return str_replace('/', '-', $nodeName);
    }
}
