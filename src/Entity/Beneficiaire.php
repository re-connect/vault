<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Api\Dto\BeneficiaryDto;
use App\Api\Filters\DistantIdFilter;
use App\Api\State\BeneficiaryStateProcessor;
use App\Api\State\BeneficiaryStateProvider;
use App\Controller\Api\UnlinkBeneficiaryController;
use App\Entity\Attributes\BeneficiaryCreationProcess;
use App\Entity\Interface\ClientResourceInterface;
use App\Traits\GedmoTimedTrait;
use App\Validator\Constraints\UniqueExternalLink;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/** @ORM\Entity(repositoryClass="App\Repository\BeneficiaireRepository") */
#[ApiFilter(DistantIdFilter::class, properties: ['distantId'])]
#[ApiResource(
    shortName: 'beneficiary',
    operations: [
        new Get(
            security: "is_granted('READ', object)",
            provider: BeneficiaryStateProvider::class,
        ),
        new Patch(
            security: "is_granted('UPDATE', object)",
            processor: BeneficiaryStateProcessor::class,
        ),
        new Patch(
            uriTemplate: '/beneficiaries/{id}/unlink',
            controller: UnlinkBeneficiaryController::class,
            openapiContext: [
                'summary' => 'Unlink a beneficiary from your oauth2 client',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
                'tags' => ['Beneficiaires'],
            ],
            description: 'Unlink a beneficiary from your oauth2 client',
            security: "is_granted('UPDATE', object)"
        ),
        new GetCollection(security: "is_granted('ROLE_OAUTH2_BENEFICIARIES')"),
        new Post(input: BeneficiaryDto::class, processor: BeneficiaryStateProcessor::class),
    ],
    normalizationContext: ['groups' => ['v3:beneficiary:read', 'v3:user:read', 'v3:center:read', 'timed']],
    denormalizationContext: ['groups' => ['v3:beneficiary:write', 'v3:user:write']],
    openapiContext: ['tags' => ['Beneficiaires']],
    security: "is_granted('ROLE_OAUTH2_BENEFICIARIES')"
)]
class Beneficiaire extends Subject implements UserWithCentresInterface, ClientResourceInterface
{
    use GedmoTimedTrait;

    #[Groups(['read', 'write', 'beneficiary:read', 'v3:beneficiary:write', 'v3:beneficiary:read'])]
    private ?\DateTime $dateNaissance = null;

    private bool $neverClickedMesDocuments = true;

    #[Groups(['beneficiary:read'])]
    private ?string $lieuNaissance;

    /** @var Collection<int, BeneficiaireCentre> $beneficiairesCentres */
    #[Groups(['read', 'beneficiary:read', 'v3:beneficiary:read'])]
    #[SerializedName('centres')]
    private Collection $beneficiairesCentres;

    /** @var Collection<int, ConsultationCentre> */
    private Collection $consultationsCentre;

    /** @var Collection<int, Document> */
    private Collection $documents;

    /** @var Collection<int, Dossier> */
    private Collection $dossiers;

    /** @var Collection<int, Contact> */
    private Collection $contacts;

    /** @var Collection<int, Note> */
    private Collection $notes;

    /** @var Collection<int, Evenement> */
    private Collection $evenements;

    private ?string $archiveName = null;

    private Collection $sms;

    #[Groups(['read', 'beneficiary:read', 'v3:beneficiary:read'])]
    private int $totalFileSize = 0;

    private bool $isCreating = true;

    private ?string $activationSmsCode = null;

    private ?\DateTime $activationSmsCodeLastSend = null;

    /** @var Collection<int, ConsultationBeneficiaire> */
    private Collection $consultationsBeneficiaires;

    #[Groups(['read', 'beneficiary:read'])]
    private ?int $idRosalie;

    #[Groups(['read', 'beneficiary:read'])]
    private ?string $siSiaoNumber = null;

    private ?User $creePar;

    #[Groups(['read', 'beneficiary:read', 'v3:beneficiary:read'])]
    private ?string $questionSecrete = null;

    #[Groups(['write'])]
    private ?string $reponseSecrete = null;

    /** @var Collection<ClientBeneficiaire> */
    #[UniqueExternalLink]
    private Collection $externalLinks;

    /** @var ?ArrayCollection<int, Centre> */
    public ?ArrayCollection $relays = null;

    public ?string $questionSecreteChoice = '';

    public ?string $autreQuestionSecrete = '';

    #[Groups(['v3:beneficiary:read', 'v3:beneficiary:write'])]
    public ?string $distantId = '';

    #[ORM\OneToOne(mappedBy: 'beneficiary', targetEntity: BeneficiaryCreationProcess::class)]
    private ?BeneficiaryCreationProcess $creationProcess = null;

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
    }

    public static function getArQuestionsSecrete(): array
    {
        return [
            'membre.creationBeneficiaire.questionsSecretes.q1' => 'membre.creationBeneficiaire.questionsSecretes.q1',
            'membre.creationBeneficiaire.questionsSecretes.q2' => 'membre.creationBeneficiaire.questionsSecretes.q2',
            'membre.creationBeneficiaire.questionsSecretes.q3' => 'membre.creationBeneficiaire.questionsSecretes.q3',
            'membre.creationBeneficiaire.questionsSecretes.q4' => 'membre.creationBeneficiaire.questionsSecretes.q4',
            'membre.creationBeneficiaire.questionsSecretes.q5' => 'membre.creationBeneficiaire.questionsSecretes.q5',
            'membre.creationBeneficiaire.questionsSecretes.q6V2' => 'membre.creationBeneficiaire.questionsSecretes.q6V2',
            'membre.creationBeneficiaire.questionsSecretes.q7' => 'membre.creationBeneficiaire.questionsSecretes.q7',
            'membre.creationBeneficiaire.questionsSecretes.q8' => 'membre.creationBeneficiaire.questionsSecretes.q8',
            'membre.creationBeneficiaire.questionsSecretes.q9' => 'membre.creationBeneficiaire.questionsSecretes.q9',
        ];
    }

    public function getReponseSecrete(): ?string
    {
        return $this->reponseSecrete;
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
        $this->beneficiairesCentres[] = $beneficiairesCentre;
        $beneficiairesCentre->setBeneficiaire($this);

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

    public function getnbDocuments(): int
    {
        return $this->getDocuments()->count();
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

    public function getEvenements(?bool $accesPrive = true, ?bool $archive = true, ?bool $beforeNow = true): Collection
    {
        $criteria = Criteria::create()->orderBy(['date' => Criteria::ASC]);
        if (!$beforeNow) {
            $criteria->andWhere(Criteria::expr()->gt('date', new \DateTime()));
        }
        if (!$accesPrive) {
            $criteria->andWhere(Criteria::expr()->eq('bPrive', false));
        }
        if (!$archive) {
            $criteria->andWhere(Criteria::expr()->eq('archive', false));
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
    public function getUsersCentres(): Collection
    {
        return $this->getBeneficiairesCentres();
    }

    public function isBeneficiaire(): bool
    {
        return true;
    }

    public function isMembre(): bool
    {
        return false;
    }

    public function getTotalFileSize(): ?int
    {
        return $this->totalFileSize;
    }

    public function setTotalFileSize(?int $totalFileSize): self
    {
        $this->totalFileSize = $totalFileSize;

        return $this;
    }

    public function getIsCreating(): bool
    {
        return $this->isCreating;
    }

    public function setIsCreating(?bool $isCreating): self
    {
        $this->isCreating = $isCreating;

        return $this;
    }

    public function getActivationSmsCode(): ?string
    {
        return $this->activationSmsCode;
    }

    public function setActivationSmsCode(?string $activationSmsCode): self
    {
        $this->activationSmsCode = $activationSmsCode;

        return $this;
    }

    public function getActivationSmsCodeLastSend(): ?\DateTime
    {
        return $this->activationSmsCodeLastSend;
    }

    public function setActivationSmsCodeLastSend(?\DateTime $activationSmsCodeLastSend): self
    {
        $this->activationSmsCodeLastSend = $activationSmsCodeLastSend;

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

    public function getCreePar(): ?User
    {
        return $this->creePar;
    }

    public function setCreePar(User $creePar = null): self
    {
        $this->creePar = $creePar;

        return $this;
    }

    public function getCreeParSonata(): string
    {
        if (!$this->getExternalLinks()->isEmpty()) {
            /** @var ClientBeneficiaire $client */
            $client = $this->getExternalLinks()->first();

            return $client->getClient()->getNom().' ('.$client->getDistantId().')';
        }
        if ($this->creePar) {
            return $this->creePar->toSonataString();
        }

        return '';
    }

    /** @return Collection<int, ClientBeneficiaire> */
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
    public function jsonSerialize($withUser = true, $withSecretResponse = false): mixed
    {
        $data = [
            'id' => $this->id,
            'date_naissance' => $this->dateNaissance->format(\DateTime::W3C),
            'total_file_size' => $this->totalFileSize,
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
            'total_file_size' => $this->totalFileSize,
            'centres' => $this->getCentreNoms()->toArray(),
            'question_secrete' => $this->questionSecrete,
            'reponse_secrete' => $this->reponseSecrete,
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
        $clientBeneficiaire = !$client ? null : $this->getExternalLinks()->filter(static function (ClientBeneficiaire $element) use ($client) {
            return $client === $element->getClient();
        })->first();
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
        $clientBeneficiaire = !$client ? null : $this->getExternalLinks()->filter(static function (ClientBeneficiaire $element) use ($client) {
            return $client === $element->getClient();
        })->first();

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
            $this->creePar = null;
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
                    $dossier = $this->dossiers->filter(function (Dossier $element) use ($document) {
                        return $element->getId() === $document->getDossier()->getId();
                    })->first();
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
        $evenement->setBeneficiaire(null);
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
        $note->setBeneficiaire(null);
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
        $document->setBeneficiaire(null);
        $this->documents->removeElement($document);

        return $this;
    }

    public function removeDossier(Dossier $dossier): self
    {
        $dossier->setBeneficiaire(null);
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

    public function hasExternalLinkForClient(Client $client): bool
    {
        return $this->externalLinks->exists(fn (int $key, ClientBeneficiaire $link) => $link->getClient() === $client);
    }

    public function getExternalLinkForClient(?Client $client): ?ClientBeneficiaire
    {
        return !$client
            ? null
            : $this->getExternalLinksForClient($client)->first() ?? null;
    }

    /** @return ?ArrayCollection<int, ClientBeneficiaire> */
    public function getExternalLinksForClient(?Client $client): ?ArrayCollection
    {
        return !$client
            ? null
            : $this->externalLinks->filter(fn (ClientBeneficiaire $link) => $link->getClient() === $client);
    }

    public function addClientExternalLink(Client $client, string $externalId, string $memberExternalId = null): self
    {
        if (!$this->hasExternalLinkForClient($client)) {
            $this->addExternalLink(ClientBeneficiaire::createForMember($client, $externalId, $memberExternalId));
        }

        return $this;
    }

    public function hasBeneficiaryRelayForRelay(Centre $relay): bool
    {
        return $this->beneficiairesCentres->exists(fn (int $key, BeneficiaireCentre $beneficiaireCentre) => $beneficiaireCentre->getCentre() === $relay);
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
    public function getAffiliatedRelays(): Collection
    {
        return $this->getBeneficiairesCentres()
            ->filter(
                fn (BeneficiaireCentre $beneficiaryRelay) => true === $beneficiaryRelay->getBValid(),
            )
            ->map(
                fn (BeneficiaireCentre $beneficiaryRelay) => $beneficiaryRelay->getCentre(),
            );
    }
}
