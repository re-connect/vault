<?php

namespace App\ManagerV2;

use App\Entity\Document;
use App\Entity\Dossier;
use App\ServiceV2\BucketService;
use App\ServiceV2\Traits\UserAwareTrait;
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
            $folder->setBPrive($parentFolder->getBPrive() || $folder->getBprive());
            $this->toggleVisibility($folder, $folder->getBPrive());
        }

        $this->em->flush();
    }

    public function toggleVisibility(Dossier $folder, bool $visibility): void
    {
        if (!$folder->getDossierParent()) {
            $this->toggleVisibilityRecursively($folder, $visibility);
        }
    }

    private function toggleVisibilityRecursively(Document|Dossier $data, bool $visibility): void
    {
        $data->setBPrive($visibility);

        if ($data instanceof Dossier) {
            $subData = [...$data->getSousDossiers()->toArray(), ...$data->getDocuments()->toArray()];
            foreach ($subData as $subDatum) {
                $this->toggleVisibilityRecursively($subDatum, $visibility);
            }
        }

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

    public function getAutocompleteFolderNames(): array
    {
        return array_map(fn ($name) => $this->translator->trans($name), Dossier::AUTOCOMPLETE_NAMES);
    }
}
