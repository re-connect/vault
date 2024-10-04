<?php

namespace App\Entity\Attributes;

use App\Entity\Document;
use App\Entity\Interface\ShareablePersonalData;
use App\Repository\SharedDocumentRepository;
use App\ServiceV2\Mailer\Email\ShareDocumentLinkEmail;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SharedDocumentRepository::class)]
#[ORM\Table(name: 'shareddocument')]
class SharedDocument extends SharedPersonalData
{
    #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'sharedDocuments')]
    private ?Document $document = null;

    public function __construct()
    {
        $this->sharedAt = new \DateTime('now');
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function getDocumentKey(): ?string
    {
        return $this->document?->getObjectKey();
    }

    public function setDocument(Document $document): self
    {
        $this->document = $document;

        return $this;
    }

    #[\Override]
    public function getPersonalData(): ShareablePersonalData
    {
        return $this->document;
    }

    #[\Override]
    public function setPersonalData(ShareablePersonalData $shareablePersonalData): static
    {
        if ($shareablePersonalData instanceof Document) {
            $this->document = $shareablePersonalData;
        }

        return $this;
    }

    #[\Override]
    public static function getEmailTemplateFqcn(): string
    {
        return ShareDocumentLinkEmail::class;
    }
}
