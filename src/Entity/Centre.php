<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable
 */
#[ApiResource(
    shortName: 'center',
    operations: [],
    normalizationContext: ['groups' => ['v3:center:read']],
    denormalizationContext: ['groups' => ['v3:center:write']],
    openapiContext: ['tags' => ['Centres']],
    security: "is_granted('ROLE_OAUTH2_CENTERS')",
)]
class Centre implements \JsonSerializable
{
    public const REGIONS = ['Auvergne-Rhône-Alpes', 'Bourgogne-Franche-Comté', 'Bretagne', 'Centre-Val de Loire', 'Corse', 'Grand Est', 'Hauts-de-France', 'Ile-de-France', 'Normandie', 'Nouvelle-Aquitaine', 'Occitanie', 'Pays de la Loire', 'Provence-Alpes-Côte d’Azur', 'Autre'];
    /**
     * @var \DateTime
     */
    #[Groups(['read-personal-data', 'center:read', 'read-personal-data-v2', 'v3:center:read'])]
    private $createdAt;
    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="centre_justificatif", fileNameProperty="justificatifName")
     *
     * @var File
     */
    protected $justificatifFile;
    /**
     * @var \DateTime
     */
    #[Groups(['read-personal-data', 'center:read', 'read-personal-data-v2', 'v3:center:read'])]
    private $updatedAt;
    /**
     * @var int
     */
    #[Groups(['read-personal-data', 'center:read', 'read-personal-data-v2', 'v3:center:read'])]
    private $id;
    /**
     * @var string
     */
    #[Groups(['read-personal-data', 'write-personal-data', 'center:read', 'read-personal-data-v2', 'v3:center:read', 'v3:center:write'])]
    private $nom = '';
    /**
     * @var string
     */
    #[Groups(['center:read', 'v3:center:read'])]
    private $region = '';
    /**
     * @var string
     */
    private $code;
    /**
     * @var string
     */
    #[Groups(['center:read', 'v3:center:read'])]
    private $siret;
    /**
     * @var string
     */
    #[Groups(['center:read', 'v3:center:read'])]
    private $finess;
    /**
     * @var Adresse
     */
    #[Groups(['center:read', 'v3:center:read'])]
    private $adresse;
    /**
     * @var string
     */
    #[Groups(['center:read', 'v3:center:read'])]
    private $telephone;
    /**
     * @var Collection|BeneficiaireCentre[]
     */
    private $beneficiairesCentres;
    /**
     * @var Collection
     */
    private $membresCentres;
    /**
     * @var Gestionnaire
     */
    private $gestionnaire;
    /**
     * @var TypeCentre
     */
    private $typeCentre;
    /**
     * @var Collection
     */
    private $sms;
    /**
     * @var Collection
     */
    private $consultationsCentre;
    /**
     * @var Collection
     */
    private $statistiquesCentre;
    /**
     * @var string
     */
    private $justificatifName;
    /**
     * @var \DateTime
     */
    private $dateFinCotisation;
    /**
     * @var string
     */
    private $budgetAnnuel;
    /**
     * @var int
     */
    private $smsCount = 0;
    private $map;
    /**
     * @var bool
     */
    private $test = false;
    /**
     * @var Collection|array
     */
    private $externalLinks;
    private bool $canada = false;

    #[Groups(['v3:center:read'])]
    public function getDistantIds(): ArrayCollection
    {
        return !$this->externalLinks ? new ArrayCollection([]) : $this->externalLinks->map(fn (ClientCentre $clientCentre) => $clientCentre->getDistantId());
    }

    public function __construct()
    {
        $this->beneficiairesCentres = new ArrayCollection();
        $this->membresCentres = new ArrayCollection();
        $this->externalLinks = new ArrayCollection();
        $letters = 'abcefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $this->code = substr(str_shuffle($letters), 0, 8);
    }

    /**
     * @return File
     */
    public function getJustificatifFile()
    {
        return $this->justificatifFile;
    }

    /**
     * @throws \Exception
     */
    public function setJustificatifFile(File $justificatif = null): void
    {
        $this->justificatifFile = $justificatif;
        if ($justificatif) {
            $this->updatedAt = new \DateTime('now');
        }
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function addBeneficiaire(Beneficiaire $beneficiaire): self
    {
        $beneficiaireCentre = new BeneficiaireCentre();
        $beneficiaireCentre->setCentre($this)->setBeneficiaire($beneficiaire);
        $this->addBeneficiairesCentre($beneficiaireCentre);

        return $this;
    }

    public function getBeneficiairesCentre()
    {
        return $this->beneficiairesCentres;
    }

    public function addBeneficiairesCentre(BeneficiaireCentre $beneficiairesCentres): self
    {
        $this->beneficiairesCentres[] = $beneficiairesCentres;
        $beneficiairesCentres->setCentre($this);
        $beneficiairesCentres->getBeneficiaire()->addBeneficiairesCentre($beneficiairesCentres);

        return $this;
    }

    public function addMembre(Membre $membre): self
    {
        $membreCentre = new MembreCentre();
        $membreCentre->setCentre($this)->setMembre($membre);
        $this->addMembresCentre($membreCentre);

        return $this;
    }

    public function addMembresCentre(MembreCentre $membreCentre): self
    {
        $this->membresCentres[] = $membreCentre;
        $membreCentre->setCentre($this);
        $membreCentre->getMembre()->addMembresCentre($membreCentre);

        return $this;
    }

    public function removeBeneficiairesCentre(BeneficiaireCentre $beneficiairesCentres): void
    {
        $this->beneficiairesCentres->removeElement($beneficiairesCentres);
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getBeneficiairesCentres()
    {
        return $this->beneficiairesCentres;
    }

    public function removeMembresCentre(MembreCentre $membresCentres): void
    {
        $this->membresCentres->removeElement($membresCentres);
    }

    /**
     * @return Collection|MembreCentre[]
     */
    public function getMembresCentres()
    {
        return $this->membresCentres;
    }

    public function getTypeCentre(): ?TypeCentre
    {
        return $this->typeCentre;
    }

    public function setTypeCentre(TypeCentre $typeCentre = null): self
    {
        $this->typeCentre = $typeCentre;

        return $this;
    }

    public function addSm(SMS $sms): self
    {
        $this->sms[] = $sms;

        return $this;
    }

    public function removeSm(SMS $sms): void
    {
        $this->sms->removeElement($sms);
    }

    /**
     * @return Collection
     */
    public function getSms()
    {
        return $this->sms;
    }

    public function addConsultationsCentre(ConsultationCentre $consultationsCentre): self
    {
        $this->consultationsCentre[] = $consultationsCentre;

        return $this;
    }

    public function removeConsultationsCentre(ConsultationCentre $consultationsCentre): void
    {
        $this->consultationsCentre->removeElement($consultationsCentre);
    }

    /**
     * @return Collection
     */
    public function getConsultationsCentre()
    {
        return $this->consultationsCentre;
    }

    public function addStatistiquesCentre(StatistiqueCentre $statistiquesCentre): self
    {
        $this->statistiquesCentre[] = $statistiquesCentre;

        return $this;
    }

    public function removeStatistiquesCentre(StatistiqueCentre $statistiquesCentre): void
    {
        $this->statistiquesCentre->removeElement($statistiquesCentre);
    }

    /**
     * @return Collection
     */
    public function getStatistiquesCentre()
    {
        return $this->statistiquesCentre;
    }

    public function __toString(): string
    {
        if (null !== $this->nom) {
            return $this->nom;
        }

        return '';
    }

    public function getBudgetAnnuel(): ?string
    {
        return $this->budgetAnnuel;
    }

    public function setBudgetAnnuel(?string $budgetAnnuel): self
    {
        $this->budgetAnnuel = $budgetAnnuel;

        return $this;
    }

    public function getJustificatifName(): ?string
    {
        return $this->justificatifName;
    }

    public function setJustificatifName(?string $justificatifName): self
    {
        $this->justificatifName = $justificatifName;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function isPayedForNow(): bool
    {
        return null != $this->getDateFinCotisation() && $this->getDateFinCotisation() > new \DateTime();
    }

    public function getDateFinCotisation(): ?\DateTime
    {
        return $this->dateFinCotisation;
    }

    public function setDateFinCotisation(?\DateTime $dateFinCotisation): self
    {
        $this->dateFinCotisation = $dateFinCotisation;

        return $this;
    }

    public function getSmsCount(): int
    {
        return $this->smsCount;
    }

    public function setSmsCount(?int $smsCount = 0): self
    {
        $this->smsCount = $smsCount;

        return $this;
    }

    public function getMap()
    {
        return $this->map;
    }

    public function setMap($map): Centre
    {
        $this->map = $map;

        return $this;
    }

    public function getTest(): ?bool
    {
        return $this->test;
    }

    public function setTest(?bool $test = false): self
    {
        $this->test = $test;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): self
    {
        $this->siret = $siret;

        return $this;
    }

    public function getFiness(): ?string
    {
        return $this->finess;
    }

    public function setFiness(?string $finess): self
    {
        $this->finess = $finess;

        return $this;
    }

    public function getAdresse(): ?Adresse
    {
        return $this->adresse;
    }

    public function setAdresse(Adresse $adresse = null): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
            'siret' => $this->siret,
            'finess' => $this->finess,
            'adresse' => $this->adresse,
        ];
    }

    public function jsonSerializeSoft(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
            'siret' => $this->siret,
            'finess' => $this->finess,
            'adresse' => $this->adresse,
        ];
    }

    public function jsonSerializeForClient($client): array
    {
        /** @var ClientCentre $clientEntity */
        $clientEntity = $this->getExternalLinks()->filter(static function (ClientCentre $element) use ($client) {
            return $client === $element->getClient();
        })->first();

        return ['distant_id' => $clientEntity?->getDistantId(), 'nom' => $this->nom];
    }

    public function getExternalLinks(): Collection
    {
        return $this->externalLinks;
    }

    public function addExternalLink(ClientCentre $externalLink): self
    {
        $this->externalLinks[] = $externalLink;
        $externalLink->setEntity($this);

        return $this;
    }

    public function removeExternalLink(ClientCentre $externalLink): bool
    {
        return $this->externalLinks->removeElement($externalLink);
    }

    public function isCanada(): bool
    {
        return $this->canada;
    }

    public function setCanada(bool $canada): self
    {
        $this->canada = $canada;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->adresse = clone $this->adresse;
            $this->consultationsCentre = [];
            $this->externalLinks = [];
            $this->sms = [];
            $this->statistiquesCentre = [];
            $this->typeCentre = clone $this->typeCentre;
            $this->canada = true;
            $beneficiairesCentres = new ArrayCollection();
            foreach ($this->beneficiairesCentres as $beneficiairesCentre) {
                $beneficiairesCentres->add(clone $beneficiairesCentre);
            }
            $this->beneficiairesCentres = new ArrayCollection();
            foreach ($beneficiairesCentres as $beneficiairesCentre) {
                $this->addBeneficiairesCentre($beneficiairesCentre);
            }
            $membresCentres = new ArrayCollection();
            foreach ($this->membresCentres as $membresCentre) {
                $membresCentres->add(clone $membresCentre);
            }
            $this->membresCentres = new ArrayCollection();
            foreach ($membresCentres as $membresCentre) {
                $this->addMembresCentre($membresCentre);
            }
        }
    }

    public function getGestionnaire(): ?Gestionnaire
    {
        return $this->gestionnaire;
    }

    public function setGestionnaire(Gestionnaire $gestionnaire = null): self
    {
        $this->gestionnaire = $gestionnaire;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region = null): self
    {
        $this->region = $region;

        return $this;
    }
}
