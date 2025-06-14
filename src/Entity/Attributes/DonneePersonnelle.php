<?php

namespace App\Entity\Attributes;

use App\Entity\Traits\CreatorTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\MappedSuperclass]
abstract class DonneePersonnelle implements \JsonSerializable, \Stringable
{
    use CreatorTrait;

    public const PRIVE = true;
    public const PARTAGE = false;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([
        'v3:document:read', 'v3:folder:read', 'v3:event:read', 'v3:note:read', 'v3:contact:read',
        'document:read', 'read-personal-data', 'read-personal-data-v2',
    ])]
    protected ?int $id = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups([
        'v3:document:read', 'v3:folder:read', 'v3:event:read', 'v3:note:read', 'v3:contact:read',
        'v3:document:write', 'v3:folder:write', 'v3:event:write', 'v3:note:write', 'v3:contact:write',
        'document:read', 'read-personal-data', 'read-personal-data-v2',
        'write-personal-data', 'write-personal-data-v2',
    ])]
    protected bool $bPrive = self::PARTAGE;

    #[ORM\Column(name: 'nom', type: 'string', length: 255)]
    #[Groups([
        'v3:document:read', 'v3:folder:read', 'v3:event:read', 'v3:note:read', 'v3:contact:read',
        'v3:document:write', 'v3:folder:write', 'v3:event:write', 'v3:note:write', 'v3:contact:write',
        'document:read', 'read-personal-data', 'read-personal-data-v2',
        'write-personal-data', 'write-personal-data-v2',
    ])]
    #[Anonymize('reconnect.personal_data_name')]
    protected ?string $nom = null;

    #[Groups([
        'v3:document:read', 'v3:folder:read', 'v3:event:read', 'v3:note:read', 'v3:contact:read',
        'v3:document:write', 'v3:folder:write', 'v3:event:write', 'v3:note:write', 'v3:contact:write',
        'document:read', 'read-personal-data', 'read-personal-data-v2', ])]
    protected ?Beneficiaire $beneficiaire = null;

    #[Groups([
        'v3:contact:read', 'v3:note:read', 'v3:event:read',
        'v3:contact:write', 'v3:note:write', 'v3:event:write', 'v3:folder:read', 'v3:folder:write'])]
    public ?int $beneficiaireId = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups([
        'v3:document:read', 'v3:folder:read', 'v3:event:read', 'v3:note:read', 'v3:contact:read',
        'document:read', 'read-personal-data', 'read-personal-data-v2', ])]
    protected \DateTime $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups([
        'v3:document:read', 'v3:folder:read', 'v3:event:read', 'v3:note:read', 'v3:contact:read',
        'document:read', 'read-personal-data', 'read-personal-data-v2', ])]
    protected \DateTime $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'deposePar_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $deposePar = null;

    public function __construct()
    {
        $this->creators = new ArrayCollection();
    }

    public static function getArBPrive(): array
    {
        return [
            'private' => self::PRIVE,
            'shared' => self::PARTAGE,
        ];
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBPrive(): ?bool
    {
        return $this->bPrive;
    }

    public function setBPrive(?bool $bPrive): static
    {
        $this->bPrive = $bPrive ?? false;

        return $this;
    }

    /**
     * Dans le cadre de l'admin.
     */
    public function isPrivateToString(): string
    {
        if ($this->bPrive) {
            return 'privé';
        }

        return 'partagé';
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom = ''): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getBeneficiaire(): ?Beneficiaire
    {
        return $this->beneficiaire;
    }

    public function setBeneficiaire(?Beneficiaire $beneficiaire = null): static
    {
        $this->beneficiaire = $beneficiaire;

        return $this;
    }

    #[Groups([
        'v3:document:read', 'v3:folder:read', 'v3:event:read', 'v3:note:read', 'v3:contact:read',
        'document:read', 'read-personal-data', 'read-personal-data-v2', ])]
    public function getBeneficiaireId(): int
    {
        return $this->beneficiaire?->getId();
    }

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->nom;
    }

    public function getDeposePar(): ?User
    {
        return $this->deposePar;
    }

    public function setDeposePar(?User $deposePar = null): static
    {
        $this->deposePar = $deposePar;

        return $this;
    }

    public function addCreator(Creator $creator): self
    {
        $this->creators[] = $creator;
        $creator->setPersonalData($this);

        return $this;
    }

    public function removeCreator(Creator $creator): bool
    {
        return $this->creators->removeElement($creator);
    }

    /**
     * @return Collection<int, Creator>
     */
    public function getCreators(): Collection
    {
        return $this->creators;
    }

    public function setPrivate(bool $isPrivate): static
    {
        $this->bPrive = $isPrivate;

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->bPrive;
    }

    public function toggleVisibility(): void
    {
        $this->setPrivate(!$this->isPrivate());
    }

    public function makePrivate(): void
    {
        $this->setPrivate(true);
    }
}
