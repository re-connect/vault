<?php

namespace App\Entity;

use App\Traits\GedmoTimedTrait;
use Symfony\Component\Serializer\Annotation\Groups;

abstract class UserCentre implements \JsonSerializable
{
    use GedmoTimedTrait;

    /**
     * @var int
     */
    protected $id;

    #[Groups(['v3:center:read', 'v3:center:write'])]
    private ?bool $bValid = false;

    /**
     * @var Membre
     */
    private $initiateur;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get bValid.
     *
     * @return bool
     */
    public function getBValid()
    {
        return $this->bValid;
    }

    /**
     * Set bValid.
     *
     * @param bool $bValid
     *
     * @return UserCentre
     */
    public function setBValid($bValid)
    {
        $this->bValid = $bValid;

        return $this;
    }

    /**
     * Get initiateur.
     *
     * @return Membre
     */
    public function getInitiateur()
    {
        return $this->initiateur;
    }

    public function setInitiateur(?Membre $initiateur): self
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

    abstract public function setUser(User $user): self;

    public function getDroits(): array
    {
        return [];
    }

    public function hasDroit(string $droit): bool
    {
        return array_key_exists($droit, $this->getDroits()) && true === $this->getDroits()[$droit];
    }
}
