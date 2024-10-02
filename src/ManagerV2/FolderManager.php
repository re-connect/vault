<?php

namespace App\ManagerV2;

use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Repository\DossierRepository;
use App\ServiceV2\BucketService;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipStream\Exception\OverflowException;
use ZipStream\ZipStream;

class FolderManager
{
    use UserAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BucketService $bucketService,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        private readonly DossierRepository $folderRepository,
        private readonly Security $security,
        private readonly ApiClientManager $apiClientManager,
    ) {
    }

    /**
     * @return Dossier[]
     */
    public function getFolders(Beneficiaire $beneficiary, ?Dossier $parentFolder = null, ?string $search = null): array
    {
        return $this->folderRepository->findByBeneficiary(
            $beneficiary,
            $this->isLoggedInUser($beneficiary->getUser()),
            $parentFolder,
            $search,
        );
    }

    /**
     * @return Collection<int, Dossier>
     */
    public function getRootFolders(Beneficiaire $beneficiary): Collection
    {
        return $beneficiary->getRootFolders($this->getUser() === $beneficiary->getUser());
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

    public function getZipFromFolder(Dossier $folder, bool $forceAddPrivateDocuments = false): ?StreamedResponse
    {
        return 0 === $folder->getDocuments()->count()
            ? null
            : new StreamedResponse(
                fn () => $this->createZipFromFolder($folder, $forceAddPrivateDocuments),
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

    private function createZipFromFolder(Dossier $folder, bool $forceAddPrivateDocuments = false): void
    {
        $zip = new ZipStream(outputName: sprintf('%s.zip', $folder->getNom()));

        $addPrivateDocuments = $forceAddPrivateDocuments || $this->getUser() === $folder->getBeneficiaire()->getUser();

        $documents = $folder->getDocuments($addPrivateDocuments)
            ->filter(fn (Document $document) => is_resource($this->bucketService->getObjectStream($document->getObjectKey())));

        foreach ($documents as $document) {
            $objectStream = $this->bucketService->getObjectStream($document->getObjectKey());
            if ($objectStream) {
                $zip->addFileFromStream(
                    $document->getNom(),
                    $objectStream,
                );
            }
        }

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

    public function getOrCreateClientFolder(Beneficiaire $beneficiary): ?Dossier
    {
        $client = $this->apiClientManager->getCurrentOldClient();

        if (Client::CLIENT_ROSALIE !== $client?->getNom()) {
            return null;
        }

        $folder = $beneficiary->getDossiers()->filter(fn (Dossier $folder) => $folder->getNom() === $client->getDossierNom())->first();

        if (!$folder) {
            $folder = (new Dossier())
                ->setBeneficiaire($beneficiary)
                ->setNom($client->getDossierNom());
            $this->em->persist($folder);
        }

        return $folder;
    }
}
