<?php

namespace App\Entity\Attributes;

use App\Entity\Dossier;
use App\Repository\SharedFolderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SharedFolderRepository::class)]
#[ORM\Table(name: 'shared_folder')]
class SharedFolder extends SharedPersonalData
{
    #[ORM\ManyToOne(targetEntity: Dossier::class, inversedBy: 'sharedFolders')]
    private ?Dossier $folder = null;

    public function __construct()
    {
        $this->sharedAt = new \DateTime('now');
    }

    #[\Override]
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFolder(): ?Dossier
    {
        return $this->folder;
    }

    public function setFolder(?Dossier $folder): static
    {
        $this->folder = $folder;

        return $this;
    }
}
