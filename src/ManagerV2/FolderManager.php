<?php

namespace App\ManagerV2;

use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\Dossier;
use App\Repository\DossierRepository;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class FolderManager
{
    use UserAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
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
