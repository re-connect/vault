<?php

namespace App\ManagerV2;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Interface\FolderableEntityInterface;
use Doctrine\ORM\EntityManagerInterface;

class FolderableEntityManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function move(FolderableEntityInterface $folderableEntity, ?Dossier $folder): void
    {
        if ($folderableEntity instanceof Dossier) {
            $this->moveFolder($folderableEntity, $folder);
        } elseif ($folderableEntity instanceof Document) {
            $this->moveDocument($folderableEntity, $folder);
        }
    }

    private function moveDocument(Document $document, ?Dossier $folder): void
    {
        if (!$folder) {
            $document->setDossier();
        } else {
            $folder->addDocument($document);
            $document->setBPrive($folder->getBPrive());
        }

        $this->em->flush();
    }

    private function moveFolder(Dossier $folder, ?Dossier $parentFolder): void
    {
        if (!$parentFolder) {
            $folder->setDossierParent();
        } elseif ($parentFolder !== $folder) {
            $parentFolder->addSousDossier($folder);
            if ($folder->getBPrive() !== $parentFolder->getBPrive()) {
                $folder->toggleVisibility();
            }
        }

        $this->em->flush();
    }
}
