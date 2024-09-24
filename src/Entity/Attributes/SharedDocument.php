<?php

namespace App\Entity\Attributes;

use App\Entity\Document;
use App\Repository\SharedDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SharedDocumentRepository::class)]
#[ORM\Table(name: 'shareddocument')]
class SharedDocument extends SharedPersonalData
{
    #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'sharedDocuments')]
    private ?Document $document = null;

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function getDocumentKey(): ?string
    {
        return $this->document?->getObjectKey();
    }

    public function setDocument($document): self
    {
        $this->document = $document;

        return $this;
    }
}
