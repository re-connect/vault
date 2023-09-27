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
    public const PERMISSIONS = [
        self::TYPEDROIT_GESTION_BENEFICIAIRES,
        self::TYPEDROIT_GESTION_MEMBRES,
    ];

    #[Groups(['v3:center:read', 'v3:center:write'])]
    private ?Centre $centre = null;

    #[Groups(['v3:center:read', 'v3:center:write'])]
    private ?Membre $membre = null;

    private ?array $droits = [];

    public static function getArDroits()
    {
        return [
            self::TYPEDROIT_GESTION_BENEFICIAIRES => 'membre.droits.gestionBeneficiaires',
            self::TYPEDROIT_GESTION_MEMBRES => 'membre.droits.gestionMembres',
        ];
    }

    public function __construct()
    {
        parent::__construct();
        $this->droits = [
            self::TYPEDROIT_GESTION_BENEFICIAIRES => true,
            self::TYPEDROIT_GESTION_MEMBRES => false,
        ];
    }

    public function getCentre(): ?Centre
    {
        return $this->centre;
    }

    public function setCentre(Centre $centre = null): static
    {
        $this->centre = $centre;

        return $this;
    }

    public function getMembre(): ?Membre
    {
        return $this->membre;
    }

    public function setMembre(Membre $membre = null): static
    {
        $this->membre = $membre;

        return $this;
    }

    public function getDroits(): array
    {
        return $this->droits;
    }

    public function setDroits($droits = []): static
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

    public function canManageProfessionals(): bool
    {
        return array_key_exists(self::TYPEDROIT_GESTION_MEMBRES, $this->getDroits())
            && true === $this->getDroits()[self::TYPEDROIT_GESTION_MEMBRES];
    }

    public function togglePermission(string $permission): void
    {
        if (!in_array($permission, self::PERMISSIONS)) {
            return;
        }

        if (!array_key_exists($permission, $this->droits)) {
            $this->droits[$permission] = true;

            return;
        }

        $this->droits[$permission] = !$this->droits[$permission];
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
