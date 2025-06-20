<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Api\ApiOperations;
use App\Api\Filters\FolderIdFilter;
use App\Api\State\FolderTreeStateProvider;
use App\Api\State\PersonalDataStateProcessor;
use App\Entity\Interface\ClientResourceInterface;
use App\Entity\Interface\FolderableEntityInterface;
use App\Repository\DossierRepository;
use App\Validator\Constraints\Folder as AssertFolder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: DossierRepository::class)]
#[ORM\Table(name: 'dossier')]
#[ORM\Index(columns: ['deposePar_id'], name: 'IDX_3D48E037F2AB781')]
#[ORM\Index(columns: ['beneficiaire_id'], name: 'IDX_3D48E0375AF81F68')]
#[ORM\Index(columns: ['dossier_parent_id'], name: 'IDX_3D48E037BC336E0D')]
#[ORM\Index(columns: ['icon_id'], name: 'IDX_3D48E03754B9D732')]
#[ApiResource(
    shortName: 'folder',
    operations: [
        new Delete(security: "is_granted('UPDATE', object) and is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_OAUTH2_DOCUMENTS_READ') or is_granted('UPDATE', object)"),
        new GetCollection(security: "is_granted('ROLE_OAUTH2_DOCUMENTS_READ') or is_granted('ROLE_USER')"),
        new GetCollection(
            uriTemplate: '/beneficiaries/{id}/folders_tree',
            uriVariables: [
                'id' => new Link(
                    fromProperty: 'dossiers',
                    fromClass: Beneficiaire::class,
                ),
            ],
            normalizationContext: ['groups' => ['v3:folder_only:read']],
            security: "is_granted('ROLE_OAUTH2_FOLDERS_READ')",
            provider: FolderTreeStateProvider::class,
        ),

        new Post(security: "is_granted('ROLE_USER') or is_granted('ROLE_OAUTH2_DOCUMENTS_CREATE')", processor: PersonalDataStateProcessor::class),
        new Patch(security: "is_granted('UPDATE', object) and is_granted('ROLE_USER')"),
        new Patch(
            uriTemplate: '/folders/{id}/toggle-visibility',
            security: "is_granted('ROLE_OAUTH2_DOCUMENTS_UPDATE')",
            name: ApiOperations::TOGGLE_VISIBILITY.'_folder',
            processor: PersonalDataStateProcessor::class
        ),
    ],
    normalizationContext: ['groups' => ['v3:folder:read']],
    denormalizationContext: ['groups' => ['v3:folder:write']],
    openapiContext: ['tags' => ['Dossiers']],
)]
#[ApiFilter(FolderIdFilter::class, properties: ['folderId' => 'exact'])]
#[ApiResource(
    uriTemplate: '/beneficiaries/{id}/folders',
    operations: [new GetCollection()],
    uriVariables: [
        'id' => new Link(
            fromProperty: 'dossiers',
            fromClass: Beneficiaire::class
        ),
    ],
    normalizationContext: ['groups' => ['v3:folder:read']],
    denormalizationContext: ['groups' => ['v3:folder:write']],
    openapiContext: ['tags' => ['Folders']],
    security: "is_granted('ROLE_OAUTH2_BENEFICIARIES_READ')",
)]
class Dossier extends DonneePersonnelle implements FolderableEntityInterface, ClientResourceInterface
{
    final public const array AUTOCOMPLETE_NAMES = ['health', 'housing', 'identity', 'tax', 'work'];
    final public const string DEFAULT_ICON_FILE_PATH = 'img/folder_icon/neutral.svg';

    #[ORM\ManyToOne(targetEntity: Beneficiaire::class, inversedBy: 'dossiers')]
    #[ORM\JoinColumn(name: 'beneficiaire_id', referencedColumnName: 'id', nullable: false)]
    protected ?Beneficiaire $beneficiaire = null;

    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: Document::class, cascade: ['persist', 'remove'])]
    #[Groups(['read-personal-data', 'read-personal-data-v2', 'v3:folder:read'])]
    private Collection $documents;

    #[Groups(['read-personal-data', 'read-personal-data-v2', 'v3:folder:read'])]
    private ?string $dossierImage = null;

    #[ORM\ManyToOne(targetEntity: Dossier::class, inversedBy: 'sousDossiers')]
    #[ORM\JoinColumn(name: 'dossier_parent_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[Groups(['read-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:folder:write', 'v3:folder:read'])]
    #[AssertFolder\NoCircularDependency]
    private ?Dossier $dossierParent = null;

    #[Groups(['v3:folder:write', 'v3:folder:read'])]
    public ?int $dossierParentId = null;

    #[ORM\OneToMany(mappedBy: 'dossierParent', targetEntity: Dossier::class, cascade: ['persist', 'remove'])]
    #[Groups(['read-personal-data', 'read-personal-data-v2', 'v3:folder_only:read'])]
    private Collection $sousDossiers;

    #[ORM\ManyToOne(targetEntity: FolderIcon::class)]
    #[ORM\JoinColumn(name: 'icon_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[Groups(['read-personal-data', 'read-personal-data-v2', 'write-personal-data-v2'])]
    private ?FolderIcon $icon = null;

    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: Creator::class, cascade: ['persist', 'remove'])]
    protected Collection $creators;

    public function __construct()
    {
        parent::__construct();
        $this->documents = new ArrayCollection();
        $this->sousDossiers = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[Groups(['read-personal-data', 'read-personal-data-v2', 'v3:folder:read'])]
    public function getIsFolder(): bool
    {
        return true;
    }

    /**
     * @return Collection|Document[]
     */
    public function getDocuments(bool $accesPrive = true)
    {
        $criteria = Criteria::create()->orderBy(['id' => Criteria::DESC]);
        if (!$accesPrive) {
            $criteria->where(Criteria::expr()->eq('bPrive', false));
        }

        return $this->documents->matching($criteria);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        $sousDossiers = $this->sousDossiers->map(fn (Dossier $sousDossier) => ['id' => $sousDossier->getId()])->toArray();

        return [
            'id' => $this->id,
            'b_prive' => $this->bPrive,
            'nom' => $this->nom,
            'created_at' => $this->createdAt->format(\DateTimeInterface::W3C),
            'updated_at' => $this->updatedAt->format(\DateTimeInterface::W3C),
            'dossier_image' => $this->getDossierImage(),
            'is_folder' => $this->getIsFolder(),
            'beneficiaire' => ['id' => $this->beneficiaire->getId()],
            'beneficiaire_id' => $this->getBeneficiaire()->getId(),
            'dossier_parent_id' => $this->dossierParent?->getId(),
            'sous_dossiers' => $sousDossiers,
        ];
    }

    public function getDossierImage(): ?string
    {
        if (null !== ($beneficaire = $this->getBeneficiaire()) && null !== ($clients = $beneficaire->getExternalLinks())) {
            foreach ($clients as $beneficiaireClient) {
                if ($beneficiaireClient->getClient()->getDossierNom() === $this->getNom()) {
                    return $beneficiaireClient->getClient()->getDossierImage();
                }
            }
        }

        return '';
    }

    public function getSousDossiers(): ArrayCollection|Collection|array
    {
        return $this->sousDossiers;
    }

    public function getVisibleSubFolders(User $user): ArrayCollection|Collection|array
    {
        return $user === $this->getBeneficiaire()?->getUser() ? $this->sousDossiers : $this->sousDossiers->filter(fn (Dossier $dossier) => !$dossier->isPrivate());
    }

    public function addSousDossier(Dossier $sousDossier): self
    {
        if (!$this->sousDossiers->contains($sousDossier)) {
            $this->sousDossiers[] = $sousDossier;
            $sousDossier->setDossierParent($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        if ($this->documents->removeElement($document) && $document->getDossier() === $this) {
            $document->setDossier(null);
            $document->setBeneficiaire(null);
        }

        return $this;
    }

    public function addDocument(Document $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents[] = $document;
            $document->setDossier($this);
        }

        return $this;
    }

    public function removeSousDossier(Dossier $dossier): self
    {
        if ($this->sousDossiers->contains($dossier)) {
            $this->sousDossiers->removeElement($dossier);
            if ($dossier->getDossierParent() === $this) {
                $dossier->setDossierParent(null);
            }
        }

        return $this;
    }

    public function getDossierParent(): ?Dossier
    {
        return $this->dossierParent;
    }

    public function setDossierParent(?Dossier $dossierParent = null): self
    {
        $this->dossierParent = $dossierParent;

        return $this;
    }

    public static function createFromParent(Dossier $parentFolder): Dossier
    {
        return (new Dossier())->setBeneficiaire($parentFolder->getBeneficiaire())->setDossierParent($parentFolder)->setBPrive($parentFolder->getBPrive());
    }

    public function hasDocuments(): bool
    {
        return $this->documents->count() || $this->sousDossiers->exists(fn (int $key, Dossier $dossier) => $dossier->hasDocuments());
    }

    #[\Override]
    public function toggleVisibility(): void
    {
        $this->setPrivate(!$this->isPrivate());

        array_map(
            fn (DonneePersonnelle $personalData) => $personalData->toggleVisibility(),
            [
                ...$this->getSousDossiers()->filter(fn (Dossier $dossier) => $dossier->isPrivate() !== $this->isPrivate())->toArray(),
                ...$this->getDocuments()->filter(fn (Document $document) => $document->isPrivate() !== $this->isPrivate())->toArray(),
            ],
        );
    }

    #[\Override]
    public function makePrivate(): void
    {
        if (!$this->isPrivate()) {
            $this->toggleVisibility();
        }
    }

    #[\Override]
    public function hasParentFolder(): bool
    {
        return null !== $this->getDossierParent();
    }

    #[\Override]
    public function canToggleVisibility(): bool
    {
        return !$this->hasParentFolder() || !$this->getDossierParent()->isPrivate();
    }

    #[\Override]
    public function move(?Dossier $parentFolder): void
    {
        $this->setDossierParent($parentFolder);

        if ($parentFolder && $parentFolder !== $this) {
            $parentFolder->addSousDossier($this);
            if ($parentFolder->isPrivate()) {
                $this->makePrivate();
            }
        }
    }

    public function isParentFolderInHierarchy(Dossier $childFolder): bool
    {
        return $this->sousDossiers->exists(fn (int $key, Dossier $subFolder) => $subFolder === $childFolder || $subFolder->isParentFolderInHierarchy($childFolder));
    }

    public function getIcon(): ?FolderIcon
    {
        return $this->icon;
    }

    public function setIcon(?FolderIcon $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    #[Groups(['read-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:folder:write', 'v3:folder:read'])]
    public function getIconFilePath(): ?string
    {
        return $this->icon?->getPublicFilePath() ?? self::DEFAULT_ICON_FILE_PATH;
    }

    public function getSluggedName(): string
    {
        return (new AsciiSlugger())->slug($this->nom);
    }

    #[\Override]
    public function getExternalLinks(): Collection
    {
        return $this->beneficiaire?->getExternalLinks();
    }

    #[\Override]
    public function hasExternalLinkForClient(Client $client): bool
    {
        return $this->beneficiaire?->hasExternalLinkForClient($client);
    }

    #[\Override]
    public function getExternalLinkForClient(Client $client): ?ClientEntity
    {
        return $this->beneficiaire?->getExternalLinkForClient($client);
    }

    #[\Override]
    public function getScopeName(): string
    {
        return 'DOCUMENTS';
    }
}
