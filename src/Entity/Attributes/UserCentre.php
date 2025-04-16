<?php

namespace App\Entity\Attributes;

use App\Entity\Membre;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\MappedSuperclass]
abstract class UserCentre implements \JsonSerializable, \Stringable
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'bValid', type: 'boolean', nullable: false)]
    #[Groups(['v3:center:read', 'v3:center:write'])]
    private bool $bValid = false;

    #[ORM\ManyToOne(targetEntity: Membre::class)]
    #[ORM\JoinColumn(name: 'initiateur_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private Membre $initiateur;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBValid(): bool
    {
        return $this->bValid;
    }

    public function setBValid(bool $bValid): static
    {
        $this->bValid = $bValid;

        return $this;
    }

    public function getInitiateur(): Membre
    {
        return $this->initiateur;
    }

    public function setInitiateur(?Membre $initiateur): static
    {
        $this->initiateur = $initiateur;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getCentre()->getNom();
    }

    abstract public function getCentre(): Centre;

    abstract public function setUser(User $user): self;

    abstract public function getUser(): ?User;

    public function getDroits(): array
    {
        return [];
    }

    public function hasDroit(string $droit): bool
    {
        return array_key_exists($droit, $this->getDroits()) && true === $this->getDroits()[$droit];
    }
}
