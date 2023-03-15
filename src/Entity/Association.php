<?php

namespace App\Entity;

use App\Traits\GedmoTimedTrait;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Association.
 */
class Association extends Subject
{
    use GedmoTimedTrait;
    public const ASSOCIATION_CATEGORIEJURIDIQUE_ASSOCIATION = 'association';
    public const ASSOCIATION_CATEGORIEJURIDIQUE_CCAS = 'ccas';
    /**
     * @var string
     */
    private $nom;

    /**
     * @var string
     */
    private $siren;
    /**
     * @var string
     */
    private $urlSite;
    /**
     * @var string
     */
    private $categorieJuridique;

    /**
     * @var Collection Centre
     */
    private Collection $centres;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
//        $this->user = new User();
    }

    public static function getAllCategories()
    {
        return [
            self::ASSOCIATION_CATEGORIEJURIDIQUE_ASSOCIATION => 'association.categorie.association',
            self::ASSOCIATION_CATEGORIEJURIDIQUE_CCAS => 'association.categorie.ccas',
        ];
    }

    /**
     * Get siren.
     *
     * @return string
     */
    public function getSiren()
    {
        return $this->siren;
    }

    /**
     * Set siren.
     *
     * @param string $siren
     *
     * @return Association
     */
    public function setSiren($siren)
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * Get urlSite.
     *
     * @return string
     */
    public function getUrlSite()
    {
        return $this->urlSite;
    }

    /**
     * Set urlSite.
     *
     * @param string $urlSite
     *
     * @return Association
     */
    public function setUrlSite($urlSite)
    {
        $this->urlSite = $urlSite;

        return $this;
    }

    /**
     * Get categorieJuridique.
     *
     * @return string
     */
    public function getCategorieJuridique()
    {
        return $this->categorieJuridique;
    }

    /**
     * Set categorieJuridique.
     *
     * @param string $categorieJuridique
     *
     * @return Association
     */
    public function setCategorieJuridique($categorieJuridique)
    {
        $this->categorieJuridique = $categorieJuridique;

        return $this;
    }

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return Association
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;
        $this->user->setTypeUser(User::USER_TYPE_ASSOCIATION);

        return $this;
    }

    public function __toString()
    {
        if (!empty($this->getNom())) {
            return $this->getNom();
        }

        return '';
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
     * Set nom.
     *
     * @param string $nom
     *
     * @return Association
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
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
        return [];
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->user = clone $this->user;
        }
    }

    /**
     * @return Collection <int, Centre>
     */
    public function getCentre(): Collection
    {
        return $this->centres;
    }

    public function addCentre(Centre $centre): self
    {
        $this->centres[] = $centre;
        $centre->setAssociation($this);

        return $this;
    }

    public function getDefaultUsername(): string
    {
        return (new AsciiSlugger())->slug($this->nom)->replaceMatches("#[ \'-]#", '')->lower()->toString();
    }
}
