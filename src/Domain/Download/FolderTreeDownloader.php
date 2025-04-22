<?php

namespace App\Domain\Download;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\Document;
use App\Entity\Attributes\Dossier;
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

class FolderTreeDownloader
{
    use UserAwareTrait;

    private array $folderPaths = [];

    public function __construct(
        private readonly BucketService $bucketService,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
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
        $documents->isEmpty()
            ? $this->addEmptyFolder($zip, $folderPath)
            : $this->addDocuments($zip, $documents, $folderPath);

        $folders = $this->getFoldersInFolder($beneficiary, $folder);

        foreach ($folders as $folder) {
            $this->addFolderContentRecursively($zip, $beneficiary, $folder, $this->formatUniquePath($folder, $folderPath));
        }
    }

    public function formatUniquePath(Dossier $folder, string $path): string
    {
        $subPath = sprintf('%s/%s', $path, $this->sanitizeNode($folder->getNom()));

        $this->folderPaths[] = $subPath;
        $duplicatedPathsCount = count(array_filter($this->folderPaths, fn (string $folderPath) => $folderPath === $subPath));

        return $duplicatedPathsCount > 1 ? sprintf('%s(%d)', $subPath, $duplicatedPathsCount - 1) : $subPath;
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

    private function addEmptyFolder(ZipStream $zip, string $folderPath): void
    {
        $zip->addDirectory($folderPath);
    }

    private function sanitizeNode(string $nodeName): string
    {
        return str_replace('/', '-', $nodeName);
    }
}
