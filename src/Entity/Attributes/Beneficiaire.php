<?php

namespace App\Entity\Attributes;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Api\Dto\BeneficiaryDto;
use App\Api\Dto\LinkBeneficiaryDto;
use App\Api\Filters\DistantIdFilter;
use App\Api\State\BeneficiaryStateProcessor;
use App\Api\State\BeneficiaryStateProvider;
use App\Controller\Api\UnlinkBeneficiaryController;
use App\Domain\Anonymization\AnonymizationHelper;
use App\Entity\Interface\ClientResourceInterface;
use App\Entity\UserWithCentresInterface;
use App\Validator\Constraints\Beneficiaire as CustomAssert;
use App\Validator\Constraints\UniqueExternalLink;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiFilter(DistantIdFilter::class, properties: ['distantId'])]
#[ApiResource(
    shortName: 'beneficiary',
    operations: [
        new Get(security: "is_granted('READ', object)", provider: BeneficiaryStateProvider::class),
        new Patch(security: "is_granted('UPDATE', object)",
            processor: BeneficiaryStateProcessor::class),
        new Patch(
            uriTemplate: '/beneficiaries/{id}/add-external-link',
            security: "is_granted('ROLE_OAUTH2_BENEFICIARIES')",
            input: LinkBeneficiaryDto::class,
            processor: BeneficiaryStateProcessor::class,
        ),
        new Patch(
            uriTemplate: '/beneficiaries/{id}/unlink',
            controller: UnlinkBeneficiaryController::class,
            openapiContext: [
                'summary' => 'Unlink a beneficiary from your oauth2 client',
                'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'object']]]],
                'tags' => ['Beneficiaires'],
            ],
            description: 'Unlink a beneficiary from your oauth2 client',
            security: "is_granted('UPDATE', object)"
        ),
        new GetCollection(security: "is_granted('ROLE_OAUTH2_BENEFICIARIES') or is_granted('ROLE_USER')"),
        new Post(
            security: "is_granted('ROLE_OAUTH2_BENEFICIARIES') or is_granted('ROLE_USER')",
            input: BeneficiaryDto::class,
            processor: BeneficiaryStateProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['v3:beneficiary:read', 'v3:user:read', 'v3:center:read', 'timed']],
    denormalizationContext: ['groups' => ['v3:beneficiary:write', 'v3:user:write']],
    openapiContext: ['tags' => ['Beneficiaires']],
    security: "is_granted('ROLE_OAUTH2_BENEFICIARIES')"
)]
#[Anonymize('reconnect.beneficiary_filter')]
#[CustomAssert\Entity(groups: ['beneficiaire'])]
#[ORM\Entity(repositoryClass: \App\Repository\BeneficiaireRepository::class)]
#[ORM\Table(name: 'beneficiaire')]
#[ORM\UniqueConstraint(name: 'UNIQ_B140D80292D7762C', columns: ['creationProcess_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_B140D802A76ED395', columns: ['user_id'])]
class Beneficiaire extends Subject implements UserWithCentresInterface, ClientResourceInterface
{
    private const string DEFAULT_BIRTHDATE = '01/01/1975';
    public const int|float MAX_VAULT_SIZE = 1024 * 1024 * 600; // 600Mo
    public const array SECRET_QUESTIONS = [
        'secret_question_mother_firstname' => 'secret_question_mother_firstnamesdf',
        'secret_question_pet' => 'secret_question_petdddddd',
        'secret_question_favorite_street' => 'secret_question_favorite_street',
        'secret_question_secondary_school' => 'secret_question_secondary_school',
        'secret_question_nickname' => 'secret_question_nickname',
        'secret_question_city_of_birth' => 'secret_question_city_of_birth',
        'secret_question_mother_lastname' => 'secret_question_mother_lastname',
        'secret_question_custom' => 'secret_question_custom',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'totalFileSize', type: 'integer', nullable: true)]
    private ?int $totalFileSize = 1000000;

    #[Groups(['read', 'beneficiary:read', 'v3:beneficiary:read'])]
    #[Assert\NotBlank(message: 'secret_question_not_empty', groups: ['beneficiaireQuestionSecrete'])]
    #[ORM\Column(name: 'questionSecrete', type: 'string', length: 255, nullable: true)]
    private ?string $questionSecrete = null;

    #[Groups(['write'])]
    #[Anonymize('string', options: ['sample' => [AnonymizationHelper::ANONYMIZED_SECRET_ANSWER]])]
    #[Assert\NotBlank(message: 'secret_answer_not_empty', groups: ['beneficiaireQuestionSecrete'])]
    #[Assert\Length(min: 3, groups: ['beneficiaireQuestionSecrete'])]
    #[ORM\Column(name: 'reponseSecrete', type: 'string', length: 255, nullable: true)]
    private ?string $reponseSecrete = null;

    #[Groups(['read', 'write', 'beneficiary:read', 'v3:beneficiary:write', 'v3:beneficiary:read'])]
    #[Anonymize('date', options: ['min' => 'now -70 years', 'max' => 'now -15 years'])]
    #[Assert\NotBlank(message: 'birthdate_not_empty', groups: ['beneficiaire'])]
    #[ORM\Column(name: 'dateNaissance', type: 'date', nullable: false)]
    private \DateTime $dateNaissance;

    #[Groups(['beneficiary:read'])]
    #[ORM\Column(name: 'lieuNaissance', type: 'string', length: 255, nullable: true)]
    private ?string $lieuNaissance = null;

    #[ORM\Column(name: 'archiveName', type: 'string', length: 255, nullable: true)]
    private ?string $archiveName = null;

    #[ORM\Column(name: 'relayInvitationSmsCode', type: 'string', length: 255, nullable: true)]
    private ?string $relayInvitationSmsCode = null;

    #[ORM\Column(name: 'relayInvitationSmsCodeSendAt', type: 'datetime', nullable: true)]
    private ?\DateTime $relayInvitationSmsCodeSendAt = null;

    #[ORM\Column(name: 'neverClickedMesDocuments', type: 'boolean', nullable: false, options: ['default' => '1'])]
    private bool $neverClickedMesDocuments = true;

    #[Groups(['read', 'beneficiary:read'])]
    #[ORM\Column(name: 'idRosalie', type: 'integer', nullable: true)]
    private ?int $idRosalie = null;

    #[Groups(['read', 'beneficiary:read'])]
    #[ORM\Column(name: 'siSiaoNumber', type: 'string', length: 255, nullable: true)]
    private ?string $siSiaoNumber = null;

    #[ORM\OneToOne(inversedBy: 'beneficiary', targetEntity: BeneficiaryCreationProcess::class)]
    #[ORM\JoinColumn(name: 'creationProcess_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?BeneficiaryCreationProcess $creationProcess = null;

    #[Assert\Valid]
    #[ORM\OneToOne(inversedBy: 'subjectBeneficiaire', targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'beneficiaire', targetEntity: Document::class, cascade: ['persist', 'remove'])]
    private Collection $documents;

    #[ORM\OneToMany(mappedBy: 'beneficiaire', targetEntity: Dossier::class, cascade: ['persist', 'remove'])]
    private Collection $dossiers;

    #[ORM\OneToMany(mappedBy: 'beneficiaire', targetEntity: Contact::class, cascade: ['persist', 'remove'])]
    private Collection $contacts;

    #[ORM\OneToMany(mappedBy: 'beneficiaire', targetEntity: Note::class, cascade: ['persist', 'remove'])]
    private Collection $notes;

    #[ORM\OneToMany(mappedBy: 'beneficiaire', targetEntity: Evenement::class, cascade: ['persist', 'remove'])]
    private Collection $evenements;

    #[Groups(['read', 'beneficiary:read', 'v3:beneficiary:read'])]
    #[SerializedName('centres')]
    #[ORM\OneToMany(mappedBy: 'beneficiaire', targetEntity: BeneficiaireCentre::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $beneficiairesCentres;

    #[ORM\OneToMany(mappedBy: 'beneficiaire', targetEntity: ConsultationCentre::class, cascade: ['persist', 'remove'])]
    private Collection $consultationsCentre;

    #[ORM\OneToMany(mappedBy: 'beneficiaire', targetEntity: SMS::class, cascade: ['persist', 'remove'])]
    private Collection $sms;

    #[ORM\OneToMany(mappedBy: 'beneficiaire', targetEntity: ConsultationBeneficiaire::class, cascade: ['persist', 'remove'])]
    private Collection $consultationsBeneficiaires;

    #[UniqueExternalLink]
    #[ORM\OneToMany(mappedBy: 'entity', targetEntity: ClientBeneficiaire::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $externalLinks;

    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['v3:beneficiary:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[Groups(['v3:beneficiary:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $updatedAt;

    /** @var ?ArrayCollection<int, Centre> */
    public ?ArrayCollection $relays = null;

    public ?string $questionSecreteChoice = '';

    public ?string $autreQuestionSecrete = '';

    #[Groups(['v3:beneficiary:read', 'v3:beneficiary:write'])]
    public ?string $distantId = '';

    public function __construct()
    {
        $this->archiveName = uniqid();
        $this->beneficiairesCentres = new ArrayCollection();
        $this->consultationsCentre = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->dossiers = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->evenements = new ArrayCollection();
        $this->sms = new ArrayCollection();
        $this->consultationsBeneficiaires = new ArrayCollection();
        $this->externalLinks = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->dateNaissance = new \DateTime(self::DEFAULT_BIRTHDATE);
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public static function getDefaultBirthDate(): \DateTime
    {
        return new \DateTime(self::DEFAULT_BIRTHDATE);
    }

    public function getReponseSecrete(): ?string
    {
        return $this->reponseSecrete;
    }

    public function getReponseSecreteToLowerCase(): ?string
    {
        return strtolower((string) $this->reponseSecrete);
    }

    public function setReponseSecrete(?string $reponseSecrete): Beneficiaire
    {
        $this->reponseSecrete = $reponseSecrete;

        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieuNaissance;
    }

    public function setLieuNaissance(?string $lieuNaissance): self
    {
        $this->lieuNaissance = $lieuNaissance;

        return $this;
    }

    #[\Override]
    public function setUser(?User $user = null): self
    {
        $this->user = $user;
        $this->user->setTypeUser(User::USER_TYPE_BENEFICIAIRE);
        $this->user->setSubjectBeneficiaire($this);

        return $this;
    }

    public function addCentre(Centre $centre): self
    {
        $beneficiairesCentre = new BeneficiaireCentre();
        $beneficiairesCentre->setCentre($centre);
        $this->addBeneficiairesCentre($beneficiairesCentre);

        return $this;
    }

    public function addBeneficiairesCentre(BeneficiaireCentre $beneficiairesCentre): self
    {
        if (!$this->beneficiairesCentres->contains($beneficiairesCentre)) {
            $this->beneficiairesCentres[] = $beneficiairesCentre;
            $beneficiairesCentre->setBeneficiaire($this);
        }

        return $this;
    }

    public function removeBeneficiairesCentre(BeneficiaireCentre $beneficiairesCentres): void
    {
        $this->beneficiairesCentres->removeElement($beneficiairesCentres);
    }

    public function getBeneficiairesCentresStr(): ?string
    {
        /** @var BeneficiaireCentre[] $centres */
        $str = '';
        $centres = $this->getBeneficiairesCentres();
        foreach ($centres as $key => $centre) {
            $str .= $centre->getCentre()->getNom();
            if ($centres->last() !== $centre) {
                $str .= ' / ';
            }
        }

        return $str;
    }

    /** @return Collection<int, BeneficiaireCentre> */
    public function getBeneficiairesCentres(): Collection
    {
        return $this->beneficiairesCentres;
    }

    public function getDocumentsCount(): int
    {
        return $this->getDocuments()->count();
    }

    public function getNotesCount(): int
    {
        return $this->getNotes()->count();
    }

    public function getContactsCount(): int
    {
        return $this->getContacts()->count();
    }

    public function getEventsCount(): int
    {
        return $this->getEvenements()->count();
    }

    /** @return Collection<int, Document> */
    public function getDocuments(?bool $isBeneficiaire = true, mixed $dossier = null): Collection
    {
        $criteria = Criteria::create()->orderBy(['id' => Criteria::DESC]);
        if (!$isBeneficiaire) {
            $criteria->andWhere(Criteria::expr()->eq('bPrive', false));
        }
        if (null !== $dossier) {
            $dossierCriteriaValue = 'root' === $dossier ? null : $dossier;
            $criteria->andWhere(Criteria::expr()->eq('dossier', $dossierCriteriaValue));
        }

        return $this->documents->matching($criteria);
    }

    public function addConsultationsCentre(ConsultationCentre $consultationsCentre): self
    {
        $this->consultationsCentre[] = $consultationsCentre;
        $consultationsCentre->setBeneficiaire($this);

        return $this;
    }

    public function removeConsultationsCentre(ConsultationCentre $consultationsCentre): void
    {
        $this->consultationsCentre->removeElement($consultationsCentre);
    }

    /** @return Collection<int, ConsultationCentre> */
    public function getConsultationsCentre(): Collection
    {
        return $this->consultationsCentre;
    }

    /** @return Collection<int, Dossier> */
    public function getDossiers(?bool $isBeneficiaire = true): Collection
    {
        $criteria = Criteria::create()->orderBy(['nom' => Criteria::ASC]);
        if (!$isBeneficiaire) {
            $criteria->where(Criteria::expr()->eq('bPrive', false));
        }

        return $this->dossiers->matching($criteria);
    }

    /** @return Collection<int, Contact> */
    public function getContacts(?bool $isBeneficiaire = true): Collection
    {
        $criteria = Criteria::create()->orderBy(['id' => Criteria::DESC]);
        if (!$isBeneficiaire) {
            $criteria->where(Criteria::expr()->eq('bPrive', false));
        }

        return $this->contacts->matching($criteria);
    }

    public function getNotes(?bool $accesPrive = true): Collection
    {
        $criteria = Criteria::create()->orderBy(['id' => Criteria::DESC]);
        if (!$accesPrive) {
            $criteria->where(Criteria::expr()->eq('bPrive', false));
        }

        return $this->notes->matching($criteria);
    }

    public function getEvenements(?bool $accesPrive = true, ?bool $beforeNow = true): Collection
    {
        $criteria = Criteria::create()->orderBy(['date' => Criteria::ASC]);
        if (!$beforeNow) {
            $criteria->andWhere(Criteria::expr()->gt('date', new \DateTime()));
        }
        if (!$accesPrive) {
            $criteria->andWhere(Criteria::expr()->eq('bPrive', false));
        }

        return $this->evenements->matching($criteria);
    }

    public function getArchiveName(): ?string
    {
        return $this->archiveName;
    }

    public function setArchiveName(?string $archiveName): self
    {
        $this->archiveName = $archiveName;

        return $this;
    }

    public function addSm(SMS $sms): self
    {
        $this->sms[] = $sms;
        $sms->setBeneficiaire($this);

        return $this;
    }

    public function removeSm(SMS $sms): void
    {
        $sms->setBeneficiaire(null);
        $this->sms->removeElement($sms);
    }

    public function getSms(): Collection
    {
        return $this->sms;
    }

    #[\Override]
    public function getUserCentre(Centre $centre): ?BeneficiaireCentre
    {
        foreach ($this->getBeneficiairesCentres() as $beneficiaireCentre) {
            if ($beneficiaireCentre->getCentre() === $centre) {
                return $beneficiaireCentre;
            }
        }

        return null;
    }

    /** @return Collection<int, BeneficiaireCentre> */
    #[\Override]
    public function getUserCentres(): Collection
    {
        return $this->getBeneficiairesCentres();
    }

    /** @return Collection<int, BeneficiaireCentre> */
    #[\Override]
    public function getUsersCentres(): Collection
    {
        return $this->getBeneficiairesCentres();
    }

    #[\Override]
    public function getUserCentresCount()
    {
        return $this->getBeneficiairesCentres()->count();
    }

    #[\Override]
    public function isBeneficiaire(): bool
    {
        return true;
    }

    #[\Override]
    public function isMembre(): bool
    {
        return false;
    }

    #[Groups(['read', 'beneficiary:read', 'v3:beneficiary:read'])]
    public function getTotalFileSize(): ?int
    {
        return $this->documents->reduce(fn (int $acc, Document $document) => $acc + $document->getTaille(), 0);
    }

    public function isCreating(): bool
    {
        return $this->creationProcess?->isCreating() ?? false;
    }

    public function isCreatingToString(): string
    {
        return $this->creationProcess?->isCreating() ? 'Oui' : 'Non';
    }

    public function getActivationSmsCode(): ?string
    {
        return $this->relayInvitationSmsCode;
    }

    public function getRelayInvitationSmsCode(): ?string
    {
        return $this->relayInvitationSmsCode;
    }

    public function setActivationSmsCode(?string $relayInvitationSmsCode): self
    {
        $this->relayInvitationSmsCode = $relayInvitationSmsCode;

        return $this;
    }

    public function setRelayInvitationSmsCode(?string $relayInvitationSmsCode): self
    {
        $this->relayInvitationSmsCode = $relayInvitationSmsCode;
        $this->relayInvitationSmsCodeSendAt = new \DateTime('now');

        return $this;
    }

    public function getActivationSmsCodeLastSend(): ?\DateTime
    {
        return $this->relayInvitationSmsCodeSendAt;
    }

    public function getRelayInvitationSmsCodeSendAt(): ?\DateTime
    {
        return $this->relayInvitationSmsCodeSendAt;
    }

    public function setActivationSmsCodeLastSend(?\DateTime $relayInvitationSmsCodeSendAt): self
    {
        $this->relayInvitationSmsCodeSendAt = $relayInvitationSmsCodeSendAt;

        return $this;
    }

    public function setRelayInvitationSmsCodeSendAt(?\DateTime $relayInvitationSmsCodeSendAt): self
    {
        $this->relayInvitationSmsCodeSendAt = $relayInvitationSmsCodeSendAt;

        return $this;
    }

    public function addConsultationsBeneficiaire(ConsultationBeneficiaire $consultationsBeneficiaire): self
    {
        $this->consultationsBeneficiaires[] = $consultationsBeneficiaire;
        $consultationsBeneficiaire->setBeneficiaire($this);

        return $this;
    }

    /**
     * Remove consultationsBeneficiaire.
     */
    public function removeConsultationsBeneficiaire(ConsultationBeneficiaire $consultationsBeneficiaire): void
    {
        $consultationsBeneficiaire->setBeneficiaire(null);
        $this->consultationsBeneficiaires->removeElement($consultationsBeneficiaire);
    }

    /** @return Collection<int, ConsultationBeneficiaire> */
    public function getConsultationsBeneficiaires(): Collection
    {
        return $this->consultationsBeneficiaires;
    }

    /** @return Collection<int, ClientBeneficiaire> */
    #[\Override]
    public function getExternalLinks(): Collection
    {
        return $this->externalLinks;
    }

    public function getIdRosalie(): ?int
    {
        return $this->idRosalie;
    }

    public function setIdRosalie(?int $idRosalie = null): Beneficiaire
    {
        $this->idRosalie = $idRosalie;

        return $this;
    }

    public function getSiSiaoNumber(): ?string
    {
        return $this->siSiaoNumber;
    }

    public function setSiSiaoNumber(?string $siSiaoNumber): self
    {
        $this->siSiaoNumber = $siSiaoNumber;

        return $this;
    }

    public function hasNeverClickedMesDocuments(): bool
    {
        return $this->neverClickedMesDocuments;
    }

    public function getNeverClickedMesDocuments(): bool
    {
        return $this->neverClickedMesDocuments;
    }

    public function setNeverClickedMesDocuments(?bool $neverClickedMesDocuments): void
    {
        $this->neverClickedMesDocuments = $neverClickedMesDocuments;
    }

    public function getQuestionSecrete(): ?string
    {
        return $this->questionSecrete;
    }

    public function setQuestionSecrete(?string $questionSecrete): Beneficiaire
    {
        $this->questionSecrete = $questionSecrete;

        return $this;
    }

    public function getDateNaissanceStr(): ?string
    {
        return $this->dateNaissance?->format('d/m/Y');
    }

    /** @return Collection<int, Centre> */
    public function getCentres(): Collection
    {
        /** @var Centre[]|Collection $centres */
        $centres = new ArrayCollection();
        foreach ($this->beneficiairesCentres as $beneficiairesCentre) {
            $centres->add($beneficiairesCentre->getCentre());
        }

        return $centres;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @param bool $withUser
     * @param bool $withSecretResponse
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    #[\Override]
    public function jsonSerialize($withUser = true, $withSecretResponse = false): mixed
    {
        $data = [
            'id' => $this->id,
            'date_naissance' => $this->dateNaissance->format(\DateTime::W3C),
            'total_file_size' => $this->getTotalFileSize(),
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
            'centres' => $this->getCentreNoms()->toArray(),
            'question_secrete' => $this->questionSecrete,
        ];
        if ($withUser) {
            $data['user'] = $this->user;
        }
        if ($withSecretResponse) {
            $data['reponse_secrete'] = $this->reponseSecrete;
        }

        return $data;
    }

    public function getCentreNoms(): Collection
    {
        $centres = new ArrayCollection();
        foreach ($this->beneficiairesCentres as $beneficiairesCentre) {
            $centres->add($beneficiairesCentre->getCentre()->getNom());
        }

        return $centres;
    }

    public function jsonSerializeAPI(): array
    {
        return [
            'subject_id' => $this->id,
            'date_naissance' => $this->dateNaissance->format(\DateTime::W3C),
            'total_file_size' => $this->getTotalFileSize(),
            'centres' => $this->getCentreNoms()->toArray(),
            'question_secrete' => $this->questionSecrete,
            'reponse_secrete' => $this->reponseSecrete,
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
        ];
    }

    public function addExternalLink(ClientBeneficiaire $externalLink): self
    {
        $this->externalLinks[] = $externalLink;
        $externalLink->setEntity($this);

        return $this;
    }

    public function removeExternalLink(ClientBeneficiaire $externalLink): bool
    {
        $externalLink->setBeneficiaireCentre(null);

        return $this->externalLinks->removeElement($externalLink);
    }

    public function jsonSerializeForClient(?Client $client): array
    {
        $clientBeneficiaire = !$client ? null : $this->getExternalLinks()->filter(static fn (ClientBeneficiaire $element) => $client === $element->getClient())->first();
        $distantId = $clientBeneficiaire?->getDistantId();

        return [
            'idRosalie' => $distantId,
            'nom' => $this->getUser()->getNom(),
            'prenom' => $this->getUser()->getPrenom(),
            'username' => $this->getUser()->getUserIdentifier(),
            'email' => $this->getUser()->getEmail(),
            'dateDeNaissance' => $this->getDateNaissance()->format(\DateTime::ISO8601),
            'telephone' => $this->getUser()->getTelephone(),
            'distant_id' => $distantId,
        ];
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTime $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        $this->user?->setBirthDate($dateNaissance);

        return $this;
    }

    public function jsonSerializeForClientV2(?Client $client): array
    {
        $clientBeneficiaire = !$client ? null : $this->getExternalLinks()->filter(static fn (ClientBeneficiaire $element) => $client === $element->getClient())->first();

        return [
            'distant_id' => $clientBeneficiaire?->getDistantId(),
            'prenom' => $this->getUser()->getPrenom(),
            'nom' => $this->getUser()->getNom(),
            'username' => $this->getUser()->getUsername(),
            'email' => $this->getUser()->getEmail(),
            'date_naissance' => $this->getDateNaissance()->format(\DateTime::ATOM),
            'telephone' => $this->getUser()->getTelephone(),
        ];
    }

    public function addCreator(Creator $creator): self
    {
        $this->user->addCreator($creator);

        return $this;
    }

    public function getCreatorUser(): false|CreatorUser|null
    {
        return $this->user->getCreatorUser();
    }

    /** @return Collection<int, CreatorUser> */
    public function getCreators(): Collection
    {
        return $this->user->getCreators();
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->user = clone $this->user;
            $this->beneficiairesCentres = new ArrayCollection();
            $this->consultationsBeneficiaires = new ArrayCollection();
            $this->consultationsCentre = new ArrayCollection();
            $this->externalLinks = new ArrayCollection();
            $this->sms = new ArrayCollection();
            $contacts = [];
            foreach ($this->contacts as $contact) {
                $contacts[] = clone $contact;
                $this->removeContact($contact);
            }
            $this->contacts = new ArrayCollection();
            foreach ($contacts as $contact) {
                $contact->setBeneficiaire($this);
                $this->addContact($contact);
            }
            $dossiers = new ArrayCollection();
            foreach ($this->dossiers as $dossier) {
                $dossiers->add(clone $dossier);
            }
            $this->dossiers = new ArrayCollection();
            foreach ($dossiers as $dossier) {
                $this->addDossier($dossier);
            }
            $documents = new ArrayCollection();
            foreach ($this->documents as $document) {
                $documents->add(clone $document);
            }
            $this->documents = new ArrayCollection();
            foreach ($documents as $document) {
                if (null !== ($dossier = $document->getDossier())) {
                    $dossier = $this->dossiers->filter(fn (Dossier $element) => $element->getId() === $document->getDossier()->getId())->first();
                }
                $document->setDossier($dossier);
                $this->addDocument($document);
            }
            // ##
            $evenements = [];
            foreach ($this->evenements as $evenement) {
                $this->removeEvenement($evenement);
                $evenements[] = clone $evenement;
            }
            $this->evenements = new ArrayCollection();
            foreach ($evenements as $evenement) {
                $this->addEvenement($evenement);
            }
            $notes = [];
            foreach ($this->notes as $note) {
                $this->removeNote($note);
                $notes[] = clone $note;
            }
            $this->notes = new ArrayCollection();
            foreach ($notes as $note) {
                $this->addNote($note);
            }
        }
    }

    public function removeContact(Contact $contact): self
    {
        $contact->setBeneficiaire(null);
        $this->contacts->removeElement($contact);

        return $this;
    }

    public function addContact(Contact $contact): self
    {
        $this->contacts[] = $contact;
        $contact->setBeneficiaire($this);

        return $this;
    }

    public function addDossier(Dossier $dossier): self
    {
        $this->dossiers[] = $dossier;
        $dossier->setBeneficiaire($this);

        return $this;
    }

    public function addDocument(Document $document): self
    {
        $this->documents[] = $document;
        $document->setBeneficiaire($this);

        return $this;
    }

    public function removeEvenement(Evenement $evenement): self
    {
        $evenement->setBeneficiaire();
        $this->evenements->removeElement($evenement);

        return $this;
    }

    public function addEvenement(Evenement $evenement): self
    {
        $this->evenements[] = $evenement;
        $evenement->setBeneficiaire($this);

        return $this;
    }

    public function removeNote(Note $note): self
    {
        $note->setBeneficiaire();
        $this->notes->removeElement($note);

        return $this;
    }

    public function addNote(Note $note): self
    {
        $this->notes[] = $note;
        $note->setBeneficiaire($this);

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        $document->setBeneficiaire();
        $this->documents->removeElement($document);

        return $this;
    }

    public function removeDossier(Dossier $dossier): self
    {
        $dossier->setBeneficiaire();
        $this->dossiers->removeElement($dossier);

        return $this;
    }

    /**
     * @return Collection<int, Dossier>
     */
    public function getRootFolders(): Collection
    {
        $criteria = Criteria::create()->andWhere(Criteria::expr()->isNull('dossierParent'));

        return $this->dossiers->matching($criteria);
    }

    /**
     * @return Collection<int, Dossier>
     */
    public function getPrivateRootFolders(): Collection
    {
        return $this->getRootFolders()->filter(fn (Dossier $folder) => $folder->isPrivate());
    }

    /**
     * @return Collection<int, Dossier>
     */
    public function getSharedRootFolders(): Collection
    {
        return $this->getRootFolders()->filter(fn (Dossier $folder) => !$folder->isPrivate());
    }

    /**
     * @return Collection<int, Dossier>
     */
    public function getRootDocuments(): Collection
    {
        $criteria = Criteria::create()->andWhere(Criteria::expr()->isNull('dossier'));

        return $this->documents->matching($criteria);
    }

    public function getRegionToString(): string
    {
        return implode(', ', array_unique(array_map(fn (Centre $centre) => $centre?->getRegion(), $this->getCentres()->toArray())));
    }

    public function getFirstName(): string
    {
        return $this->user?->getPrenom() ?? '';
    }

    public function getLastName(): string
    {
        return $this->user?->getNom() ?? '';
    }

    public function addCreatorRelay(Centre $relay): self
    {
        $this->user->addCreatorRelay($relay);

        return $this;
    }

    public function hasCreator(): bool
    {
        return $this->user->hasCreator();
    }

    public function addCreatorClient(Client $client): self
    {
        if (!$this->hasCreator()) {
            $this->user->addCreatorClient($client);
        }

        return $this;
    }

    #[\Override]
    public function hasExternalLinkForClient(Client $client): bool
    {
        return $this->externalLinks->exists(fn (int $key, ClientBeneficiaire $link) => $link->getClient() === $client);
    }

    #[\Override]
    public function getExternalLinkForClient(?Client $client): ?ClientBeneficiaire
    {
        return $this->getExternalLinksForClient($client)?->first() ?: null;
    }

    /** @return ?ArrayCollection<int, ClientBeneficiaire> */
    public function getExternalLinksForClient(?Client $client): ?ArrayCollection
    {
        return !$client
            ? null
            : $this->externalLinks->filter(fn (ClientBeneficiaire $link) => $link->getClient() === $client);
    }

    public function addClientExternalLink(Client $client, string $externalId, ?string $memberExternalId = null, ?BeneficiaireCentre $beneficiaireCentre = null): self
    {
        if ($this->canAddExternalLinkForClient($client, $externalId)) {
            $externalLink = ClientBeneficiaire::createForMember($client, $externalId, (int) $memberExternalId);
            $externalLink->setBeneficiaireCentre($beneficiaireCentre);
            $this->addExternalLink($externalLink);
        }

        return $this;
    }

    public function externalLinkExists(Client $client, string $distantId): bool
    {
        $links = $this->externalLinks->filter(fn (ClientBeneficiaire $link) => $link->getClient() === $client && $link->getDistantId() === $distantId);

        return count($links) > 0;
    }

    public function hasBeneficiaryRelayForRelay(Centre $relay): bool
    {
        return $this->beneficiairesCentres->exists(fn (int $key, BeneficiaireCentre $beneficiaireCentre) => $beneficiaireCentre->getCentre() === $relay);
    }

    public function addRelay(Centre $relay): self
    {
        return $this->addBeneficiaryRelayForRelay($relay);
    }

    public function addBeneficiaryRelayForRelay(Centre $relay): self
    {
        if (!$this->hasBeneficiaryRelayForRelay($relay)) {
            $this->addBeneficiairesCentre(BeneficiaireCentre::createValid($relay));
        }

        return $this;
    }

    public function getCreationProcess(): ?BeneficiaryCreationProcess
    {
        return $this->creationProcess;
    }

    public function setCreationProcess(?BeneficiaryCreationProcess $beneficiaryCreationProcess): self
    {
        $this->creationProcess = $beneficiaryCreationProcess;

        return $this;
    }

    public function getDistantId(): ?string
    {
        return $this->distantId;
    }

    public function setDistantId(?string $distantId): self
    {
        $this->distantId = $distantId;

        return $this;
    }

    #[\Override]
    public function getDefaultUsername(): string
    {
        return sprintf('%s.%s.%s',
            $this->user->getSluggedFirstName(),
            $this->user->getSluggedLastname(),
            $this->getDateNaissanceStr()
        );
    }

    /**
     * @return Collection <int, Centre>
     */
    public function getAffiliatedRelays(): ReadableCollection
    {
        return $this->getBeneficiairesCentres()
            ->filter(
                fn (BeneficiaireCentre $beneficiaryRelay) => true === $beneficiaryRelay->getBValid(),
            )
            ->map(
                fn (BeneficiaireCentre $beneficiaryRelay) => $beneficiaryRelay->getCentre(),
            );
    }

    public function relayInvitationSmsSent(): bool
    {
        return $this->relayInvitationSmsCodeSendAt && $this->relayInvitationSmsCode;
    }

    public function hasValidSmsRelayInvitationCode(): bool
    {
        return $this->relayInvitationSmsSent() && $this->relayInvitationSmsCodeSendAt > (new \DateTime())->modify('-24 hours');
    }

    public function resetAffiliationSmsCode(): self
    {
        $this->relayInvitationSmsCodeSendAt = null;
        $this->relayInvitationSmsCode = null;

        return $this;
    }

    public function hasRosalieExternalLink(): bool
    {
        return null !== $this->getRosalieExternalLink();
    }

    public function getRosalieExternalLink(): ?ClientBeneficiaire
    {
        return $this->getExternalLinks()->filter(fn (ClientBeneficiaire $link) => Client::CLIENT_ROSALIE === $link->getClient()?->getNom())->first() ?: null;
    }

    public function getReconnectProExternalLink(): ?ClientBeneficiaire
    {
        return $this->getExternalLinks()->filter(fn (ClientBeneficiaire $link) => Client::CLIENT_RECONNECT_PRO === $link->getClient()?->getNom())->first() ?: null;
    }

    public function canAddExternalLinkForClient(Client $client, string $externalId): bool
    {
        return !$this->hasExternalLinkForClient($client) || ($client->allowsMultipleLinks() && !$this->externalLinkExists($client, $externalId));
    }

    public function hasExternalLinkForRelay(Client $client, int $externalRelayId): bool
    {
        foreach ($this->getUserCentres() as $userCentre) {
            if ($userCentre->getExternalLink()?->getClient() === $client && $userCentre->getCentre()?->getDistantIdsForClient($client)->contains((string) $externalRelayId)) {
                return true;
            }
        }

        return false;
    }
}
