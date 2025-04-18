<?php

namespace App\ManagerV2;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\Document;
use App\Entity\Dossier;
use App\Entity\Interface\FolderableEntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FolderableItemManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FolderManager $folderManager,
        private readonly DocumentManager $documentManager,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function move(FolderableEntityInterface $folderableEntity, ?Dossier $folder): void
    {
        $folderableEntity->move($folder);
        $constraintViolations = $this->validator->validate($folderableEntity);

        if (0 !== count($constraintViolations)) {
            throw new \Exception($this->translator->trans($constraintViolations[0]->getMessage()));
        }

        $this->em->flush();
    }

    /**
     * @return array<Document|Dossier>
     */
    public function getFoldersAndDocumentsWithUrl(Beneficiaire $beneficiary, ?Dossier $parentFolder = null, ?string $search = null): array
    {
        return [
            ...$this->folderManager->getFolders($beneficiary, $parentFolder, $search),
            ...$this->documentManager->getDocumentsWithUrl($beneficiary, $parentFolder, $search),
        ];
    }
}
