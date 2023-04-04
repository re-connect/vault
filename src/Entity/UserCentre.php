<?php

namespace App\Entity;

use App\Traits\GedmoTimedTrait;
use Symfony\Component\Serializer\Annotation\Groups;

abstract class UserCentre implements \JsonSerializable
{
    use GedmoTimedTrait;

    protected ?int $id;

    #[Groups(['v3:center:read', 'v3:center:write'])]
    private ?bool $bValid = false;

    private ?Membre $initiateur;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBValid(): ?bool
    {
        return $this->bValid;
    }

    public function setBValid(bool $bValid): static
    {
        $this->bValid = $bValid;

        return $this;
    }

    public function getInitiateur(): ?Membre
    {
        return $this->initiateur;
    }

    public function setInitiateur(?Membre $initiateur): static
    {
        $this->initiateur = $initiateur;

        return $this;
    }

    public function __toString()
    {
        return $this->getCentre()->getNom();
    }

    /**
     * @return Centre
     */
    abstract public function getCentre();

    abstract public function setUser(User $user): static;
}
