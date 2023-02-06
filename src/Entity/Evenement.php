<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ApiResource(
    shortName: 'Event',
    operations: [new Get(), new Put(), new Patch(), new Delete(), new GetCollection(), new Post()],
    normalizationContext: ['groups' => ['v3:event:read']],
    denormalizationContext: ['groups' => ['v3:event:write']],
    openapiContext: ['tags' => ['Évènements']],
    security: "is_granted('ROLE_OAUTH2_EVENTS')",
)]
class Evenement extends DonneePersonnelle
{
    public const EVENEMENT_RAPPEL_SMS = 'SMS';
    public const EVENEMENT_RAPPEL_MAIL = 'Mail';
    #[Groups(['read-personal-data', 'write-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:event:write', 'v3:event:read'])]
    private $date;
    /**
     * @Groups({"read-personal-data", "write-personal-data", "read-personal-data-v2", "write-personal-data-v2"})
     */
    private ?string $timezone = null;
    #[Groups(['read-personal-data', 'write-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:event:write', 'v3:event:read'])]
    private ?string $lieu = null;
    #[Groups(['read-personal-data', 'write-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:event:write', 'v3:event:read'])]
    private ?string $commentaire = null;
    #[Groups(['read-personal-data', 'v3:event:write', 'v3:event:read'])]
    private bool $bEnvoye = false;
    #[Groups(['read-personal-data', 'write-personal-data', 'v3:event:write', 'v3:event:read'])]
    private ?int $heureRappel;
    private ?SMS $sms = null;
    private ?Membre $membre = null;
    #[Groups(['read-personal-data-v2', 'v3:event:write', 'v3:event:read'])]
    private bool $archive = false;
    private ?array $typeRappels = [];
    /**
     * @var Collection|Rappel[]
     */
    #[Groups(['read-personal-data', 'write-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:event:write', 'v3:event:read'])]
    private $rappels;

    public function __construct(Beneficiaire $beneficiaire = null)
    {
        parent::__construct();
        $this->beneficiaire = $beneficiaire;
        $this->date = new \DateTime();
        $this->rappels = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getDate()
    {
        $timezone = $this->timezone ?? 'Europe/Paris';
        $date = $this->date ?? new \DateTime();

        return new \DateTime($date->format('Y-m-d H:i:s'), new \DateTimeZone($timezone));
    }

    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get lieu.
     *
     * @return string
     */
    public function getLieu()
    {
        return $this->lieu;
    }

    /**
     * Set lieu.
     *
     * @param string $lieu
     *
     * @return Evenement
     */
    public function setLieu($lieu)
    {
        $this->lieu = $lieu;

        return $this;
    }

    /**
     * Get commentaire.
     *
     * @return string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * Set commentaire.
     *
     * @param string $commentaire
     *
     * @return Evenement
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * Fonction pour l'export sonata admin.
     *
     * @return string
     */
    public function getRappelsToString()
    {
        $str = '';
        if (null !== $this->rappels) {
            foreach ($this->rappels as $rappel) {
                if ('' !== $str) {
                    $str .= ', ';
                }
                $str .= $rappel;
            }
        }

        return $str;
    }

    /**
     * Fonction pour l'export sonata admin.
     *
     * @return string|null
     */
    public function getBEnvoyeToString()
    {
        if (null !== $this->rappels) {
            foreach ($this->rappels as $rappel) {
                if (self::EVENEMENT_RAPPEL_SMS === $rappel || self::EVENEMENT_RAPPEL_MAIL === $rappel) {
                    return $this->bEnvoye ? 'Oui' : 'Non';
                }
            }
        }

        return null;
    }

    /**
     * Get bEnvoye.
     *
     * @return bool
     */
    public function getBEnvoye()
    {
        return $this->bEnvoye;
    }

    /**
     * Set bEnvoye.
     *
     * @param bool $bEnvoye
     *
     * @return Evenement
     */
    public function setBEnvoye($bEnvoye)
    {
        $this->bEnvoye = $bEnvoye;

        return $this;
    }

    /**
     * Get heureRappel.
     *
     * @return int
     */
    public function getHeureRappel()
    {
        return $this->heureRappel;
    }

    /**
     * Set heureRappel.
     *
     * @param int $heureRappel
     *
     * @return Evenement
     */
    public function setHeureRappel($heureRappel)
    {
        $this->heureRappel = $heureRappel;

        return $this;
    }

    public function __toString()
    {
        if ($this->date) {
            return sprintf('%s le %s', $this->nom, $this->date->format('d/m/Y H:i'));
        }

        return $this->nom;
    }

    /**
     * Get sms.
     *
     * @return SMS
     */
    public function getSms()
    {
        return $this->sms;
    }

    /**
     * Set sms.
     *
     * @param SMS $sms
     *
     * @return Evenement
     */
    public function setSms(SMS $sms = null)
    {
        $this->sms = $sms;

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
     * @return Evenement
     */
    public function setMembre(Membre $membre = null)
    {
        $this->membre = $membre;

        return $this;
    }

    /**
     * Get archive.
     *
     * @return bool
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * Set archive.
     *
     * @param bool $archive
     *
     * @return Evenement
     */
    public function setArchive($archive)
    {
        $this->archive = $archive;

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
        return [
            'id' => $this->id,
            'b_prive' => $this->bPrive,
            'nom' => $this->nom,
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
            'commentaire' => $this->commentaire,
            'lieu' => $this->lieu,
            'date' => $this->getDate()->format(\DateTime::W3C),
            'archive' => $this->archive,
            'dateToString' => $this->getDateToString(),
            'rappels' => $this->getRappels(false)->toArray(),
            'beneficiaire_id' => $this->getBeneficiaire()->getId(),
        ];
    }

    #[Groups(['read-personal-data-v2', 'v3:event:read'])]
    #[SerializedName('dateToString')]
    public function getDateToString()
    {
        setlocale(LC_TIME, 'fr_FR');
        $str = '';
        if (null !== $this->date) {
            $toDay = date_format(new \DateTime(), 'd/m/Y');
            $date = date_format($this->date, 'd/m/Y');
            if ($toDay === $date) {
                $str = 'Aujourd\'hui';
            } else {
                $str = $date = date_format($this->date, 'd M Y');
            }
        }

        return $str;
    }

    /**
     * Get rappels.
     *
     * @param bool $archive
     *
     * @return Collection|Rappel[]
     */
    public function getRappels($archive = true)
    {
        $criteria = Criteria::create()->orderBy(['date' => Criteria::ASC]);
        if (!$archive) {
            $criteria->andWhere(Criteria::expr()->eq('archive', false));
        }

        return $this->rappels->matching($criteria);
    }

    /**
     * @param Rappel[]|Collection $rappels
     */
    public function setRappels($rappels): Evenement
    {
        $this->rappels = $rappels;

        return $this;
    }

    /**
     * Get typeRappels.
     *
     * @return array
     */
    public function getTypeRappels()
    {
        return $this->typeRappels;
    }

    /**
     * Set typeRappels.
     *
     * @param array $typeRappels
     *
     * @return Evenement
     */
    public function setTypeRappels($typeRappels)
    {
        $this->typeRappels = $typeRappels;

        return $this;
    }

    /**
     * Add rappel.
     *
     * @return Evenement
     */
    public function addRappel(Rappel $rappel)
    {
        $this->rappels[] = $rappel;
        $rappel->setEvenement($this);

        return $this;
    }

    /**
     * Remove rappel.
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeRappel(Rappel $rappel)
    {
        return $this->rappels->removeElement($rappel);
    }
}
