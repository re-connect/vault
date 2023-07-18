<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\Api\UploadDocumentController;
use App\Entity\Interface\FolderableEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DocumentRepository")
 */
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new Get(),
        new Put(),
        new Patch(),
        new Delete(),
        new GetCollection(),
        new Post(
            controller: UploadDocumentController::class,
            openapiContext: [
                'tags' => ['Documents'],
                'requestBody' => [
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => ['type' => 'file'],
                                    'distant_id' => ['type' => 'string', 'format' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            deserialize: false,
        ),
    ],
    normalizationContext: ['groups' => ['v3:document:read']],
    denormalizationContext: ['groups' => ['v3:document:write']],
    openapiContext: ['tags' => ['Documents']],
    security: "is_granted('ROLE_OAUTH2_DOCUMENTS')",
)]
class Document extends DonneePersonnelle implements FolderableEntityInterface
{
    use SoftDeleteableEntity;

    public const BROWSER_EXTENSIONS_NOT_VIEWABLE = ['doc', 'docx', 'txt', 'odt', 'xls', 'xlsx', 'csv'];
    public const BROWSER_EXTENSIONS_VIEWABLE = ['jpg', 'jpeg', 'pdf', 'gif', 'png'];
    protected ?\DateTime $dateEmission = null;

    #[Groups(['v3:document:write', 'v3:document:read'])]
    protected ?Dossier $dossier = null;

    #[Groups(['document:read', 'read-personal-data', 'read-personal-data-v2', 'v3:document:read'])]
    protected string $extension = '';

    protected ?int $taille = null;

    #[Groups(['document:read', 'read-personal-data', 'read-personal-data-v2', 'v3:document:read'])]
    protected ?string $url = null;

    #[Groups(['document:read', 'read-personal-data', 'read-personal-data-v2', 'v3:document:read'])]
    protected ?string $deleteUrl = null;

    #[Groups(['document:read', 'read-personal-data', 'read-personal-data-v2', 'v3:document:read'])]
    protected ?string $thumb = null;

    #[Groups(['document:read', 'read-personal-data', 'read-personal-data-v2', 'v3:document:read'])]
    private bool $isFolder = false;

    #[Groups(['document:read', 'v3:document:read'])]
    private ?string $presignedUrl = null;

    #[Groups(['document:read', 'v3:document:read'])]
    private ?string $thumbnailPresignedUrl = null;

    #[Groups(['document:read', 'v3:document:read'])]
    private ?string $objectKey = '';

    #[Groups(['document:read', 'v3:document:read'])]
    private ?string $thumbnailKey = null;

    #[Groups(['document:read', 'read-personal-data', 'v3:document:read'])]
    private ?string $renameUrl = null;

    #[Groups(['document:read', 'read-personal-data', 'v3:document:read'])]
    private ?string $toggleAccessUrl = null;

    #[Groups(['document:read', 'read-personal-data', 'write-personal-data', 'read-personal-data-v2', 'v3:document:read', 'v3:document:write'])]
    private ?int $folderId = null;

    #[Groups(['document:read', 'read-personal-data', 'read-personal-data-v2', 'v3:document:read'])]
    private ?string $deposeParFullName = null;

    private ?Collection $sharedDocuments;

    public function __construct()
    {
        parent::__construct();
        $this->sharedDocuments = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(Dossier $dossier = null): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getTaille(): ?int
    {
        return $this->taille;
    }

    public function setTaille(int $taille): self
    {
        $this->taille = $taille;

        return $this;
    }

    public function getNameWithoutExtension(): string
    {
        $m = [];
        if (preg_match('#(.+)\\..{2,4}$#', $this->nom, $m)) {
            return $m[1];
        }

        return $this->nom;
    }

    public function getDateEmission(): ?\DateTime
    {
        return $this->dateEmission;
    }

    public function setDateEmission(\DateTime $dateEmission): self
    {
        $this->dateEmission = $dateEmission;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getThumb(): string
    {
        return $this->thumb;
    }

    public function setThumb(string $thumb): self
    {
        $this->thumb = $thumb;

        return $this;
    }

    public function getIsFolder(): bool
    {
        return false;
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
            'url' => $this->url,
            'thumb' => $this->thumb,
            'delete_url' => $this->deleteUrl,
            'rename_url' => $this->renameUrl,
            'toggle_access_url' => $this->toggleAccessUrl,
            'is_folder' => $this->getIsFolder(),
            'extension' => $this->extension,
            'folder_id' => $this->dossier?->getId(),
            'beneficiaire' => ['id' => $this->beneficiaire->getId()],
            'depose_par_full_name' => $this->getDeposeParGetFullName(),
            'beneficiaire_id' => $this->getBeneficiaire()->getId(),
            'object_key' => $this->objectKey,
            'thumbnail_key' => $this->thumbnailKey,
            'isShared' => $this->isCurrentlyShared(),
            'daysBeforeSharingExpires' => $this->getDaysBeforeSharingExpires(),
        ];
    }

    public function getActiveSharedDocuments(): ?Collection
    {
        return $this->sharedDocuments->filter(function (SharedDocument $sharedDocument) {
            return !$sharedDocument->isExpired();
        });
    }

    public function isCurrentlyShared(): int
    {
        return $this->getActiveSharedDocuments()->count() > 0;
    }

    public function getDaysBeforeSharingExpires(): int
    {
        return max([0, ...$this->getActiveSharedDocuments()->map(function (SharedDocument $sharedDocument) {
            return round(($sharedDocument->getExpirationDate()->getTimestamp() - time()) / 86400);
        })->toArray()]);
    }

    public function getDeposeParGetFullName(): string
    {
        $fullname = '';
        if ($creatorUser = $this->getCreatorUser()) {
            $user = $creatorUser->getEntity();
            if (null !== $user) {
                $fullname = $user->getPrenom().' '.$user->getNom();
            }
        }

        return $fullname;
    }

    public function getAuthorFullName(): string
    {
        return parent::getDeposeParGetFullName();
    }

    public function getNomSubstr(): string
    {
        if (null !== $this->nom) {
            if (20 < strlen($this->nom)) {
                return substr($this->nom, 0, 20).'...';
            }

            return $this->nom;
        }

        return '';
    }

    public function getObjectKey(): string
    {
        return $this->objectKey;
    }

    public function setObjectKey(string $objectKey): self
    {
        $this->objectKey = $objectKey;

        return $this;
    }

    public function getThumbnailKey(): ?string
    {
        return $this->thumbnailKey;
    }

    public function setThumbnailKey(string $thumbnailKey = null): self
    {
        $this->thumbnailKey = $thumbnailKey;

        return $this;
    }

    public function getDeleteUrl(): ?string
    {
        return $this->deleteUrl;
    }

    public function setDeleteUrl(string $deleteUrl): void
    {
        $this->deleteUrl = $deleteUrl;
    }

    public function getPresignedUrl(): ?string
    {
        return $this->presignedUrl;
    }

    public function setPresignedUrl(string $presignedUrl): self
    {
        $this->presignedUrl = $presignedUrl;

        return $this;
    }

    public function getThumbnailPresignedUrl(): ?string
    {
        return $this->thumbnailPresignedUrl;
    }

    public function setThumbnailPresignedUrl(string $thumbnailPresignedUrl = null): self
    {
        $this->thumbnailPresignedUrl = $thumbnailPresignedUrl;

        return $this;
    }

    public function getRenameUrl(): string
    {
        return $this->renameUrl;
    }

    public function setRenameUrl(string $renameUrl): self
    {
        $this->renameUrl = $renameUrl;

        return $this;
    }

    public function getToggleAccessUrl(): string
    {
        return $this->toggleAccessUrl;
    }

    public function setToggleAccessUrl(string $toggleAccessUrl): Document
    {
        $this->toggleAccessUrl = $toggleAccessUrl;

        return $this;
    }

    public function getFolderId(): ?int
    {
        return $this->dossier?->getId();
    }

    public function setFolderId(?int $folderId): self
    {
        $this->folderId = $folderId;

        return $this;
    }

    public function getDeposeParFullName(): ?string
    {
        return $this->getCreatorUserFullName();
    }

    public function hasParentFolder(): bool
    {
        return null !== $this->getDossier();
    }

    public function move(?Dossier $parentFolder): void
    {
        $this->setDossier($parentFolder);
        if ($parentFolder) {
            $parentFolder->addDocument($this);
            $this->setBPrive($parentFolder->getBPrive());
        }
    }
}
