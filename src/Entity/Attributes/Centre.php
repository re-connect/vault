<?php

namespace App\Entity\Attributes;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\Rest\V3\LinkedCentersController;
use App\Entity\TypeCentre;
use App\Repository\CentreRepository;
use App\Validator\Constraints\UniqueExternalLink;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Table(name: 'centre')]
#[Vich\Uploadable]
#[ORM\Index(columns: ['region_id'], name: 'IDX_C6A0EA7598260155')]
#[ORM\Index(columns: ['gestionnaire_id'], name: 'IDX_C6A0EA756885AC1B')]
#[ORM\Index(columns: ['typeCentre_id'], name: 'IDX_C6A0EA7527F237FC')]
#[ORM\Index(columns: ['association_id'], name: 'IDX_C6A0EA75EFB9C8A5')]
#[ORM\UniqueConstraint(name: 'UNIQ_C6A0EA754DE7DC5C', columns: ['adresse_id'])]
#[ORM\Entity(repositoryClass: CentreRepository::class)]
#[ApiResource(
    shortName: 'center',
    operations: [
        new Get(security: "is_granted('ROLE_OAUTH2_CENTERS') or is_granted('VIEW', object)"),
        new GetCollection(security: "is_granted('ROLE_OAUTH2_CENTERS') or is_granted('ROLE_USER')"),
        new GetCollection(
            uriTemplate: '/linked-centers/{id}',
            requirements: ['id' => '\d+'],
            controller: LinkedCentersController::class,
            security: "is_granted('ROLE_OAUTH2_CENTERS')",
            deserialize: false,
        ),
    ],
    normalizationContext: ['groups' => ['v3:center:read']],
    denormalizationContext: ['groups' => ['v3:center:write']],
    openapiContext: ['tags' => ['Centres']],
    security: "is_granted('ROLE_OAUTH2_CENTERS')",
)]
#[ApiFilter(SearchFilter::class, properties: ['beneficiairesCentres.beneficiaire'])]
class Centre implements \JsonSerializable, \Stringable
{
    public const array REGIONS = ['Auvergne-Rhône-Alpes', 'Bourgogne-Franche-Comté', 'Bretagne', 'Centre-Val de Loire', 'Corse', 'Grand Est', 'Hauts-de-France', 'Ile-de-France', 'Normandie', 'Nouvelle-Aquitaine', 'Occitanie', 'Pays de la Loire', 'Provence-Alpes-Côte d’Azur', 'Autre'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read-personal-data', 'center:read', 'read-personal-data-v2', 'v3:center:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['read-personal-data', 'center:read', 'read-personal-data-v2', 'v3:center:read'])]
    private ?\DateTime $createdAt = null;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="centre_justificatif", fileNameProperty="justificatifName")
     *
     * @var File
     */
    protected $justificatifFile;

    #[ORM\Column(name: 'updatedAt', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'update')]
    #[Groups(['read-personal-data', 'center:read', 'read-personal-data-v2', 'v3:center:read'])]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 255, nullable: false)]
    #[Groups(['read-personal-data', 'write-personal-data', 'center:read', 'read-personal-data-v2', 'v3:center:read', 'v3:center:write'])]
    private string $nom = '';

    #[ORM\Column(name: 'regionAsString', type: 'string', length: 255, nullable: true)]
    #[Groups(['center:read', 'v3:center:read'])]
    private ?string $regionAsString = '';

    #[ORM\Column(name: 'code', type: 'string', length: 255, nullable: false)]
    private string $code;

    #[ORM\Column(name: 'siret', type: 'string', length: 255, nullable: true)]
    #[Groups(['center:read', 'v3:center:read'])]
    private ?string $siret = null;

    #[ORM\Column(name: 'finess', type: 'string', length: 255, nullable: true)]
    #[Groups(['center:read', 'v3:center:read'])]
    private ?string $finess = null;

    #[ORM\JoinColumn(name: 'adresse_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Adresse::class, cascade: ['persist', 'remove'])]
    #[Groups(['center:read', 'v3:center:read'])]
    private ?Adresse $adresse = null;

    #[ORM\Column(name: 'telephone', type: 'string', length: 255, nullable: true)]
    #[Groups(['center:read', 'v3:center:read'])]
    private ?string $telephone = null;

    #[ORM\OneToMany(mappedBy: 'centre', targetEntity: BeneficiaireCentre::class, cascade: ['persist', 'remove'])]
    private Collection $beneficiairesCentres;

    #[ORM\OneToMany(mappedBy: 'centre', targetEntity: MembreCentre::class, cascade: ['persist', 'remove'])]
    private Collection $membresCentres;

    #[ORM\JoinColumn(name: 'gestionnaire_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: Gestionnaire::class)]
    private ?Gestionnaire $gestionnaire = null;

    #[ORM\JoinColumn(name: 'typeCentre_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: TypeCentre::class)]
    private ?TypeCentre $typeCentre = null;

    #[ORM\OneToMany(mappedBy: 'centre', targetEntity: SMS::class, cascade: ['persist', 'remove'])]
    private Collection $sms;

    #[ORM\OneToMany(mappedBy: 'centre', targetEntity: ConsultationCentre::class, cascade: ['persist', 'remove'])]
    private ?Collection $consultationsCentre = null;

    #[ORM\OneToMany(mappedBy: 'centre', targetEntity: StatistiqueCentre::class, cascade: ['persist', 'remove'])]
    private ?Collection $statistiquesCentre = null;

    #[ORM\Column(name: 'justificatifName', type: 'string', length: 255, nullable: true)]
    private ?string $justificatifName = null;

    #[ORM\Column(name: 'dateFinCotisation', type: 'datetime', nullable: true)]
    private ?\DateTime $dateFinCotisation = null;

    #[ORM\Column(name: 'budgetAnnuel', type: 'string', length: 255, nullable: true)]
    private ?string $budgetAnnuel = null;

    #[ORM\Column(name: 'smsCount', type: 'integer', nullable: false, options: ['default' => 0])]
    private int $smsCount = 0;
    private $map;

    #[ORM\Column(name: 'test', type: 'boolean', nullable: false)]
    private bool $test = false;

    #[ORM\OneToMany(mappedBy: 'entity', targetEntity: ClientCentre::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[UniqueExternalLink]
    private Collection $externalLinks;

    #[ORM\Column(name: 'canada', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $canada = false;

    #[ORM\JoinColumn(name: 'association_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Association::class)]
    private ?Association $association = null;

    #[ORM\JoinColumn(name: 'region_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: Region::class)]
    private ?Region $region = null;

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
    public function setJustificatifFile(?File $justificatif = null): void
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

    public function setTypeCentre(?TypeCentre $typeCentre = null): self
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

    #[\Override]
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

    public function getNameAndAddress(): string
    {
        return sprintf('%s (%s)', $this->nom, $this->getAdresse());
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

    public function setAdresse(?Adresse $adresse = null): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    #[\Override]
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
        $clientEntity = $this->getExternalLinks()->filter(static fn (ClientCentre $element) => $client === $element->getClient())->first();

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

    public function setGestionnaire(?Gestionnaire $gestionnaire = null): self
    {
        $this->gestionnaire = $gestionnaire;

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region = null): self
    {
        $this->region = $region;

        return $this;
    }

    public function getAssociation(): ?Association
    {
        return $this->association;
    }

    public function setAssociation(?Association $association): self
    {
        $this->association = $association;

        return $this;
    }

    public function getRegionAsString(): string
    {
        return $this->regionAsString;
    }

    public function setRegionAsString(string $regionAsString): Centre
    {
        $this->regionAsString = $regionAsString;

        return $this;
    }
}
