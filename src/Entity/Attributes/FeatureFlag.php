<?php

namespace App\Entity\Attributes;

use App\Repository\FeatureFlagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeatureFlagRepository::class)]
#[ORM\Table(name: 'feature_flag')]
class FeatureFlag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $enabled;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $enableDate = null;

    public function __construct(#[ORM\Column(length: 255)]
        private ?string $name)
    {
        $this->enabled = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function enable(): static
    {
        $this->enabled = true;

        return $this;
    }

    public function disable(): static
    {
        $this->enabled = false;

        return $this;
    }

    public function getEnableDate(): ?\DateTimeImmutable
    {
        return $this->enableDate;
    }

    public function setEnableDate(?\DateTimeImmutable $enableDate): static
    {
        $this->enableDate = $enableDate;

        return $this;
    }

    public function isEnableDateDue(): bool
    {
        return $this->enableDate && $this->enableDate < new \DateTime();
    }

    public function shouldEnable(): bool
    {
        return !$this->isEnabled() && $this->isEnableDateDue();
    }
}
