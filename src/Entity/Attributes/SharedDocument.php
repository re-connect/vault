<?php

namespace App\Entity\Attributes;

use App\Repository\SharedDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SharedDocumentRepository::class)]
#[ORM\Table(name: 'shareddocument')]
#[ORM\Index(columns: ['sharedBy_id'], name: 'IDX_8DF667BC8CF51483')]
#[ORM\Index(columns: ['document_id'], name: 'IDX_8DF667BCC33F7837')]
class SharedDocument
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'sharedAt', type: 'datetime', nullable: false)]
    private ?\DateTime $sharedAt;

    #[ORM\Column(name: 'expirationDate', type: 'datetime', nullable: false)]
    private ?\DateTime $expirationDate = null;

    #[ORM\Column(name: 'token', type: 'text', nullable: false)]
    private ?string $token = null;

    #[ORM\Column(name: 'selector', type: 'string', length: 255, nullable: false)]
    private ?string $selector = null;

    #[ORM\Column(name: 'sharedWithEmail', type: 'string', length: 255, nullable: false)]
    private ?string $sharedWithEmail = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sharedDocuments')]
    #[ORM\JoinColumn(name: 'sharedBy_id', referencedColumnName: 'id')]
    private ?User $sharedBy = null;

    #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'sharedDocuments')]
    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id')]
    private ?Document $document = null;

    public function __construct()
    {
        $this->sharedAt = new \DateTime('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSharedAt(): \DateTime
    {
        return $this->sharedAt;
    }

    public function setSharedAt(\DateTime $sharedAt): static
    {
        $this->sharedAt = $sharedAt;

        return $this;
    }

    public function getExpirationDate(): \DateTime
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(\DateTime $expirationDate): static
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function isExpired(): bool
    {
        return new \DateTime('now') > $this->expirationDate;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function setSelector(string $selector): static
    {
        $this->selector = $selector;

        return $this;
    }

    public function getSharedWithEmail(): string
    {
        return $this->sharedWithEmail;
    }

    public function setSharedWithEmail(string $sharedWithEmail): static
    {
        $this->sharedWithEmail = $sharedWithEmail;

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function getDocumentKey(): ?string
    {
        return $this->document?->getObjectKey();
    }

    public function setDocument($document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getSharedBy(): ?User
    {
        return $this->sharedBy;
    }

    public function setSharedBy(User $sharedBy): self
    {
        $this->sharedBy = $sharedBy;

        return $this;
    }
}
