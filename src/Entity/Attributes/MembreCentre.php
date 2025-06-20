<?php

namespace App\Entity\Attributes;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'membrecentre')]
#[ORM\Index(columns: ['centre_id'], name: 'IDX_FABE0968463CD7C3')]
#[ORM\Index(columns: ['membre_id'], name: 'IDX_FABE09686A99F74A')]
#[ORM\Index(columns: ['initiateur_id'], name: 'IDX_FABE096856D142FC')]
class MembreCentre extends UserCentre
{
    public const DEFAULT_PERMISSION_CREATE_BENEFICIARIES = 'creationbeneficiaires';
    public const MANAGE_BENEFICIARIES_PERMISSION = 'gestionbeneficiaires';
    public const MANAGE_PROS_PERMISSION = 'gestionmembres';
    public const PERMISSIONS = [
        self::MANAGE_BENEFICIARIES_PERMISSION,
        self::MANAGE_PROS_PERMISSION,
    ];

    #[ORM\Column(name: 'droits', type: 'array', nullable: false)]
    private array $droits = [];

    #[ORM\ManyToOne(targetEntity: Membre::class, inversedBy: 'membresCentres')]
    #[ORM\JoinColumn(name: 'membre_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['v3:center:read', 'v3:center:write'])]
    private ?Membre $membre = null;

    #[ORM\ManyToOne(targetEntity: Centre::class, inversedBy: 'membresCentres')]
    #[ORM\JoinColumn(name: 'centre_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['v3:center:read', 'v3:center:write'])]
    private ?Centre $centre = null;

    public static function getArDroits(): array
    {
        return [
            self::MANAGE_BENEFICIARIES_PERMISSION => 'beneficiaries_management',
            self::MANAGE_PROS_PERMISSION => 'professionals_management',
        ];
    }

    public function __construct()
    {
        parent::__construct();
        $this->droits = [
            self::MANAGE_BENEFICIARIES_PERMISSION => false,
            self::MANAGE_PROS_PERMISSION => false,
        ];
    }

    #[\Override]
    public function getCentre(): ?Centre
    {
        return $this->centre;
    }

    public function setCentre(?Centre $centre = null): static
    {
        $this->centre = $centre;

        return $this;
    }

    public function getMembre(): ?Membre
    {
        return $this->membre;
    }

    public function setMembre(?Membre $membre = null): static
    {
        $this->membre = $membre;

        return $this;
    }

    #[\Override]
    public function getDroits(): array
    {
        return $this->droits;
    }

    public function setDroits(array $droits = []): static
    {
        $this->droits = $droits;

        return $this;
    }

    #[\Override]
    public function setUser(User $user): static
    {
        $this->membre = $user->getSubjectMembre();

        return $this;
    }

    #[\Override]
    public function getUser(): User
    {
        return $this->membre?->getUser();
    }

    public function canManageBeneficiaries(): bool
    {
        return array_key_exists(self::MANAGE_BENEFICIARIES_PERMISSION, $this->getDroits())
            && true === $this->getDroits()[self::MANAGE_BENEFICIARIES_PERMISSION];
    }

    public function canManageProfessionals(): bool
    {
        return array_key_exists(self::MANAGE_PROS_PERMISSION, $this->getDroits())
            && true === $this->getDroits()[self::MANAGE_PROS_PERMISSION];
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

    public function addPermission(string $permission): void
    {
        if (!in_array($permission, self::PERMISSIONS)) {
            return;
        }

        $this->droits[$permission] = true;
    }

    public function removePermission(string $permission): void
    {
        if (!in_array($permission, self::PERMISSIONS)) {
            return;
        }

        $this->droits[$permission] = false;
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
    #[\Override]
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
