<?php

namespace App\Entity;

/**
 * StatistiqueCentre.
 */
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

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $nom;

    /**
     * @var array
     */
    private $donnees;

    /**
     * @var \App\Entity\Centre
     */
    private $centre;

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
     * Set nom.
     *
     * @param string $nom
     *
     * @return StatistiquesCentre
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom.
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set donnees.
     *
     * @param array $donnees
     *
     * @return StatistiquesCentre
     */
    public function setDonnees($donnees)
    {
        $this->donnees = $donnees;

        return $this;
    }

    /**
     * Get donnees.
     *
     * @return array
     */
    public function getDonnees()
    {
        return $this->donnees;
    }

    /**
     * Set centre.
     *
     * @return StatistiqueCentre
     */
    public function setCentre(Centre $centre)
    {
        $this->centre = $centre;

        return $this;
    }

    /**
     * Get centre.
     *
     * @return \App\Entity\Centre
     */
    public function getCentre()
    {
        return $this->centre;
    }
}
