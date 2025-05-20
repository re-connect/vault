<?php

namespace App\Entity\Attributes;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'typecentre')]
class TypeCentre implements \Stringable
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 255, nullable: false)]
    private string $nom;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTime $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTime $updatedAt;

    #[ORM\OneToMany(mappedBy: 'typeCentre', targetEntity: Centre::class)]
    private Collection $centres;

    public function __construct()
    {
        $this->centres = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setNom($nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function addCentre(Centre $centres): static
    {
        $this->centres[] = $centres;

        return $this;
    }

    public function removeCentre(Centre $centres): void
    {
        $this->centres->removeElement($centres);
    }

    public function getCentres(): Collection
    {
        return $this->centres;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->nom;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->centres = new ArrayCollection();
        }
    }
}
