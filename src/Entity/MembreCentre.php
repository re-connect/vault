<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * MembreCentre.
 */
class MembreCentre extends UserCentre
{
    public const TYPEDROIT_GESTION_BENEFICIAIRES = 'gestionbeneficiaires';
    public const TYPEDROIT_GESTION_MEMBRES = 'gestionmembres';

    #[Groups(['v3:center:read', 'v3:center:write'])]
    private $centre;

    #[Groups(['v3:center:read', 'v3:center:write'])]
    private $membre;

    /**
     * @var array
     */
    private $droits = [];

    public static function getArDroits()
    {
        return [
            self::TYPEDROIT_GESTION_BENEFICIAIRES => 'membre.droits.gestionBeneficiaires',
            self::TYPEDROIT_GESTION_MEMBRES => 'membre.droits.gestionMembres',
        ];
    }

    /**
     * Get centre.
     *
     * @return Centre
     */
    public function getCentre()
    {
        return $this->centre;
    }

    /**
     * Set centre.
     *
     * @param Centre $centre
     *
     * @return MembreCentre
     */
    public function setCentre(Centre $centre = null)
    {
        $this->centre = $centre;

        return $this;
    }

    /**
     * Get membre.
     *
     * @return Membre
     */
    public function getMembre()
    {
        return $this->membre;
    }

    /**
     * Set membre.
     *
     * @param Membre $membre
     *
     * @return MembreCentre
     */
    public function setMembre(Membre $membre = null)
    {
        $this->membre = $membre;

        return $this;
    }

    /**
     * Get droits.
     *
     * @return array
     */
    public function getDroits()
    {
        return $this->droits;
    }

    /**
     * Set droits.
     *
     * @param array $droits
     *
     * @return MembreCentre
     */
    public function setDroits($droits = [])
    {
        $this->droits = $droits;

        return $this;
    }

    public function setUser(User $user): static
    {
        $this->membre = $user->getSubjectMembre();

        return $this;
    }

    public function canManageBeneficiaries(): bool
    {
        return array_key_exists(self::TYPEDROIT_GESTION_BENEFICIAIRES, $this->getDroits())
            && true === $this->getDroits()[self::TYPEDROIT_GESTION_BENEFICIAIRES];
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'b_valid' => $this->getBValid(),
            'created_at' => $this->getCreatedAt()->format(\DateTime::W3C),
            'updated_at' => $this->getUpdatedAt()->format(\DateTime::W3C),
            'centre' => $this->centre,
            'droits' => $this->droits,
        ];
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->membre = clone $this->membre;
//            $this->centre =  null;
            $this->setInitiateur(null);
        }
    }
}
