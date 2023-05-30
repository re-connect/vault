<?php

namespace App\ManagerV2;

use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Interface\FolderableEntityInterface;
use Doctrine\ORM\EntityManagerInterface;

class FolderableItemManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FolderManager $folderManager,
        private readonly DocumentManager $documentManager,
    ) {
    }

    public function move(FolderableEntityInterface $folderableEntity, ?Dossier $folder): void
    {
        $folderableEntity->move($folder);
        $this->em->flush();
    }

    /**
     * @return array<Document|Dossier>
     */
    public function getFoldersAndDocumentsWithUrl(Beneficiaire $beneficiary, Dossier $parentFolder = null, string $search = null): array
    {
        return [
            ...$this->folderManager->getFolders($beneficiary, $parentFolder, $search),
            ...$this->documentManager->getDocumentsWithUrl($beneficiary, $parentFolder, $search),
        ];
    }
}
