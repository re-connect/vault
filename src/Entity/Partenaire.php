<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[ORM\Table(name: 'partenaire')]
#[Vich\Uploadable]
class Partenaire
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 255, nullable: false)]
    private string $nom;

    #[ORM\Column(name: 'imageName', type: 'string', length: 255, nullable: false)]
    private ?string $imageName = null;

    #[ORM\Column(name: 'link', type: 'string', length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(name: 'canada', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $canada = false;
    private ?File $imageFile = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function getImageFile(): File
    {
        return $this->imageFile;
    }

    /**
     * @param File|UploadedFile $image
     *
     * @throws \Exception
     */
    public function setImageFile(?File $image = null): void
    {
        $this->imageFile = $image;
        if ($image) {
            $this->updatedAt = new \DateTime('now');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getImageName(): string
    {
        return $this->imageName;
    }

    public function setImageName(string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function isCanada(): bool
    {
        return $this->canada;
    }

    public function setCanada(bool $canada): self
    {
        $this->canada = $canada;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->canada = true;
        }
    }
}
