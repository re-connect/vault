<?php

namespace App\Entity\Attributes;

use App\Entity\Dossier;
use App\Entity\Interface\ShareablePersonalData;
use App\Repository\SharedFolderRepository;
use App\ServiceV2\Mailer\Email\ShareFolderLinkEmail;
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

    #[\Override]
    public function getPersonalData(): ShareablePersonalData
    {
        return $this->folder;
    }

    #[\Override]
    public function setPersonalData(ShareablePersonalData $shareablePersonalData): static
    {
        if ($shareablePersonalData instanceof Dossier) {
            $this->folder = $shareablePersonalData;
        }

        return $this;
    }

    #[\Override]
    public static function getEmailTemplateFqcn(): string
    {
        return ShareFolderLinkEmail::class;
    }
}
