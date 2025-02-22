<?php

namespace App\Entity;

use App\Entity\Attributes\Centre;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity('name')]
class Region implements \Stringable
{
    private ?int $id = null;

    #[Assert\NotBlank]
    private ?string $name = null;

    #[Assert\Email]
    private ?string $email = null;

    private ?Collection $centres = null;

    public function __construct()
    {
        $this->centres = new ArrayCollection();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCentres(): Collection
    {
        return $this->centres;
    }

    public function addCentre(Centre $centre): self
    {
        if (!$this->centres->contains($centre)) {
            $this->centres->add($centre);
            $centre->setRegion($this);
        }

        return $this;
    }

    public function removeCentre(Centre $centre): self
    {
        $this->centres->remove($centre);
        $centre->setRegion();

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->name;
    }
}
