<?php

namespace App\ManagerV2;

use App\Entity\Beneficiaire;
use App\Entity\Dossier;
use App\ServiceV2\BucketService;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipStream\Exception\OverflowException;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class FolderManager
{
    use UserAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BucketService $bucketService,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        private Security $security,
    ) {
    }

    public function move(Dossier $folder, ?Dossier $parentFolder): void
    {
        if (!$parentFolder) {
            $folder->setDossierParent();
        } elseif ($parentFolder !== $folder) {
            $parentFolder->addSousDossier($folder);
            if ($folder->getBPrive() !== $parentFolder->getBPrive()) {
                $this->toggleVisibility($folder);
            }
        }

        $this->em->flush();
    }

    /**
     * @return Collection<int, Dossier>
     */
    public function getRootFolders(Beneficiaire $beneficiary): Collection
    {
        return $this->getUser() === $beneficiary->getUser()
            ? $beneficiary->getRootFolders()
            : $beneficiary->getSharedRootFolders();
    }

    public function toggleVisibility(Dossier $folder): void
    {
        $folder->toggleVisibility();
        $this->em->flush();
    }

    public function delete(Dossier $folder): void
    {
        $folder->setDossierParent();
        $this->em->remove($folder);

        $this->em->flush();
    }

    public function getZipFromFolder(Dossier $folder): ?StreamedResponse
    {
        return 0 === $folder->getDocuments()->count()
            ? null
            : new StreamedResponse(
                fn () => $this->createZipFromFolder($folder),
                200,
                [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => HeaderUtils::makeDisposition(
                        HeaderUtils::DISPOSITION_ATTACHMENT,
                        sprintf('%s.zip', $folder->getNom()),
                    ),
                ]
            );
    }

    private function createZipFromFolder(Dossier $folder): void
    {
        $options = new Archive();
        $options->setZeroHeader(true);
        $zip = new ZipStream($folder->getNom(), $options);

        $folder->getDocuments($this->getUser() === $folder->getBeneficiaire()->getUser())
            ->filter(
                fn ($document) => $this->bucketService->getObjectStream($document->getObjectKey())
            )->map(
                fn ($document) => $zip->addFileFromStream(
                    $document->getNom(),
                    $this->bucketService->getObjectStream($document->getObjectKey())
                )
            );

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

    /**
     * @return string[]
     */
    public function getAutocompleteFolderNames(): array
    {
        return array_map(fn ($name) => $this->translator->trans($name), Dossier::AUTOCOMPLETE_NAMES);
    }
}
