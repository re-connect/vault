<?php

namespace App\Entity;

use App\Entity\Attributes\Document;
use App\Repository\SharedDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SharedDocumentRepository::class)]
class SharedDocument
{
    private ?int $id = null;
    private ?\DateTime $sharedAt;
    private ?\DateTime $expirationDate = null;
    private ?string $token = null;
    private ?string $selector = null;
    private ?string $sharedWithEmail = null;
    private ?User $sharedBy = null;
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

    public function setSharedAt(\DateTime $sharedAt): self
    {
        $this->sharedAt = $sharedAt;

        return $this;
    }

    public function getExpirationDate(): \DateTime
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(\DateTime $expirationDate): self
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

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function setSelector(string $selector): self
    {
        $this->selector = $selector;

        return $this;
    }

    public function getSharedWithEmail(): string
    {
        return $this->sharedWithEmail;
    }

    public function setSharedWithEmail(string $sharedWithEmail): self
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

    public function setDocument($document): self
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
