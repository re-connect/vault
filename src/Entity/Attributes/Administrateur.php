<?php

namespace App\Entity\Attributes;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'administrateur')]
#[ORM\UniqueConstraint(name: 'UNIQ_32EB52E8A76ED395', columns: ['user_id'])]
class Administrateur extends Subject
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'subjectAdministrateur', targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected ?User $user = null;

    #[\Override]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[\Override]
    public function setUser(?User $user = null): static
    {
        $this->user = $user;
        $this->user->setTypeUser(User::USER_TYPE_ADMINISTRATEUR);

        return $this;
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [];
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->user = null;
        }
    }
}
