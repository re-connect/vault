<?php

namespace App\Entity\Attributes;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'statistiquecentre')]
#[ORM\Index(columns: ['centre_id'], name: 'IDX_E9AFD40E463CD7C3')]
class StatistiqueCentre
{
    public const STATISTIQUECENTRE_NB_BENEFICIAIRES = 'nbBeneficiaires';
    public const STATISTIQUECENTRE_NB_MEMBRES = 'nbMembres';
    public const STATISTIQUECENTRE_SMS_ENVOYES = 'smsEnvoyes';

    public static function getArStats()
    {
        return [
            self::STATISTIQUECENTRE_NB_BENEFICIAIRES => 'admin.centres.statistiques.nbBeneficiaires',
            self::STATISTIQUECENTRE_NB_MEMBRES => 'admin.centres.statistiques.nbMembres',
            self::STATISTIQUECENTRE_SMS_ENVOYES => 'admin.centres.statistiques.smsEnvoyes',
        ];
    }

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 255, nullable: false)]
    private $nom;

    #[ORM\Column(name: 'donnees', type: 'array', nullable: false)]
    private $donnees;

    #[ORM\ManyToOne(targetEntity: Centre::class)]
    #[ORM\JoinColumn(name: 'centre_id', referencedColumnName: 'id', nullable: false)]
    private Centre $centre;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setDonnees(array $donnees): static
    {
        $this->donnees = $donnees;

        return $this;
    }

    public function getDonnees(): array
    {
        return $this->donnees;
    }

    public function setCentre(Centre $centre): static
    {
        $this->centre = $centre;

        return $this;
    }

    public function getCentre(): Centre
    {
        return $this->centre;
    }
}
