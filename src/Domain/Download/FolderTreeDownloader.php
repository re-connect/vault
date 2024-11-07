<?php

namespace App\Domain\Download;

use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Security\VoterV2\PersonalDataVoter;
use App\ServiceV2\BucketService;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use ZipStream\Exception\OverflowException;
use ZipStream\ZipStream;

readonly class FolderTreeDownloader
{
    use UserAwareTrait;

    public function __construct(
        private BucketService $bucketService,
        private Security $security,
        private LoggerInterface $logger,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function downloadZip(Beneficiaire $beneficiary, Dossier $folder): ?StreamedResponse
    {
        return 0 === $folder->getDocuments()->count()
            ? null
            : new StreamedResponse(
                fn () => $this->createZip($beneficiary, $folder),
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

    private function createZip(Beneficiaire $beneficiary, Dossier $folder): void
    {
        $zip = new ZipStream(outputName: sprintf('%s.zip', $folder->getNom()));

        $this->addFolderContentRecursively($zip, $beneficiary, $folder);

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

    public function addFolderContentRecursively(ZipStream $zip, Beneficiaire $beneficiary, ?Dossier $folder = null, string $folderPath = ''): void
    {
        $documents = $this->getDocumentsInFolder($beneficiary, $folder);
        $this->addDocuments($zip, $documents, $folderPath);

        $folders = $this->getFoldersInFolder($beneficiary, $folder);
        foreach ($folders as $folder) {
            $this->addFolderContentRecursively($zip, $beneficiary, $folder, sprintf('%s/%s', $folderPath, $this->sanitizeNode($folder->getNom())));
        }
    }

    /**
     * @return Collection<int, Document>
     */
    private function getDocumentsInFolder(Beneficiaire $beneficiary, ?Dossier $folder = null): Collection
    {
        $documents = $folder?->getDocuments() ?? $beneficiary->getRootDocuments();

        return $documents->filter(
            fn (Document $document) => is_resource($this->bucketService->getObjectStream($document->getObjectKey()))
                && $this->authorizationChecker->isGranted(PersonalDataVoter::DOWNLOAD, $document)
        );
    }

    /**
     * @return Collection<int, Dossier>
     */
    private function getFoldersInFolder(Beneficiaire $beneficiary, ?Dossier $folder = null): Collection
    {
        $folders = $folder?->getSousDossiers() ?? $beneficiary->getRootFolders();

        return $folders->filter(
            fn (Dossier $folder) => $this->authorizationChecker->isGranted(PersonalDataVoter::DOWNLOAD, $folder)
        );
    }

    /**
     * @param Collection<int, Document> $documents
     */
    private function addDocuments(ZipStream $zip, Collection $documents, string $folderPath): void
    {
        foreach ($documents as $document) {
            $documentName = $this->sanitizeNode($document->getNom());
            $objectStream = $this->bucketService->getObjectStream($document->getObjectKey());
            if ($objectStream) {
                $zip->addFileFromStream(
                    sprintf('%s/%s', $folderPath, $documentName),
                    $objectStream,
                );
            }
        }
    }

    private function sanitizeNode(string $nodeName): string
    {
        return str_replace('/', '-', $nodeName);
    }
}
