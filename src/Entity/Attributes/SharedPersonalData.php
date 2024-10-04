<?php

namespace App\Entity\Attributes;

use App\Entity\Interface\ShareablePersonalData;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

abstract class SharedPersonalData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $sharedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $expirationDate = null;

    #[ORM\Column(type: Types::TEXT)]
    protected ?string $token = null;

    #[ORM\Column(length: 255)]
    protected ?string $selector = null;

    #[ORM\Column(length: 255)]
    protected ?string $sharedWithEmail = null;

    #[ORM\ManyToOne(inversedBy: 'sharedPersonalData')]
    protected ?User $sharedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSharedAt(): ?\DateTimeInterface
    {
        return $this->sharedAt;
    }

    public function setSharedAt(\DateTimeInterface $sharedAt): static
    {
        $this->sharedAt = $sharedAt;

        return $this;
    }

    public function getExpirationDate(): ?\DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(\DateTimeInterface $expirationDate): static
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function isExpired(): bool
    {
        return new \DateTime('now') > $this->expirationDate;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getSelector(): ?string
    {
        return $this->selector;
    }

    public function setSelector(string $selector): static
    {
        $this->selector = $selector;

        return $this;
    }

    public function getSharedWithEmail(): ?string
    {
        return $this->sharedWithEmail;
    }

    public function setSharedWithEmail(string $sharedWithEmail): static
    {
        $this->sharedWithEmail = $sharedWithEmail;

        return $this;
    }

    public function getSharedBy(): ?User
    {
        return $this->sharedBy;
    }

    public function setSharedBy(?User $sharedBy): static
    {
        $this->sharedBy = $sharedBy;

        return $this;
    }

    abstract public function getPersonalData(): ShareablePersonalData;

    abstract public function setPersonalData(ShareablePersonalData $shareablePersonalData): static;

    abstract public static function getEmailTemplateFqcn(): string;
}
