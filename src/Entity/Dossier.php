<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Interface\FolderableEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DocumentRepository")
 */
#[ApiResource(
    shortName: 'folder',
    operations: [new Get(), new GetCollection(), new Post(), new Put(), new Patch(), new Delete()],
    normalizationContext: ['groups' => ['v3:folder:read']],
    denormalizationContext: ['groups' => ['v3:folder:write']],
    openapiContext: ['tags' => ['Dossiers']],
    security: "is_granted('ROLE_OAUTH2_DOCUMENTS')",
)]
class Dossier extends DonneePersonnelle implements FolderableEntityInterface
{
    final public const AUTOCOMPLETE_NAMES = ['health', 'housing', 'identity', 'tax', 'work'];
    /**
     * @OneToMany(targetEntity="App\Entity\Document", mappedBy="dossier")
     */
    #[Groups(['read-personal-data', 'read-personal-data-v2', 'v3:folder:read'])]
    private Collection $documents;
    #[Groups(['read-personal-data', 'read-personal-data-v2', 'v3:folder:read'])]
    private ?string $dossierImage = null;
    /**
     * @Groups({ "read-personal-data", "read-personal-data-v2", "write-personal-data-v2" })
     *
     * @ManyToOne(targetEntity="App\Entity\Dossier", inversedBy="sousDossiers")
     *
     * @JoinColumn(name="dossier_parent_id")
     */
    #[Groups(['read-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:folder:write', 'v3:folder:read'])]
    private ?Dossier $dossierParent = null;
    /**
     * @OneToMany(targetEntity="App\Entity\Dossier", mappedBy="dossierParent")
     */
    #[Groups(['read-personal-data', 'read-personal-data-v2'])]
    private Collection $sousDossiers;

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

    public function jsonSerialize(): array
    {
        $sousDossiers = $this->sousDossiers->map(function (Dossier $sousDossier) {
            return ['id' => $sousDossier->getId()];
        })->toArray();

        return [
            'id' => $this->id,
            'b_prive' => $this->bPrive,
            'nom' => $this->nom,
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
            'documents' => $this->documents->toArray(),
            'dossier_image' => $this->getDossierImage(),
            'is_folder' => $this->getIsFolder(),
            'beneficiaire' => ['id' => $this->beneficiaire->getId()],
            'beneficiaire_id' => $this->getBeneficiaire()->getId(),
            'dossier_parent_id' => $this->dossierParent?->getId(),
            'sous_dossiers' => $sousDossiers,
        ];
    }

    /**
     * get the folder image that was assigned to the client.
     */
    public function getDossierImage()
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

    /**
     * @return Collection|Dossier[]
     */
    public function getSousDossiers()
    {
        return $this->sousDossiers;
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

    public function setDossierParent(Dossier $dossierParent = null): self
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

    public function toggleVisibility(): void
    {
        $this->setBPrive(!$this->getBPrive());

        array_map(
            fn (DonneePersonnelle $personalData) => $personalData->toggleVisibility(),
            [
            ...$this->getSousDossiers()->filter(fn (Dossier $dossier) => $dossier->getBPrive() !== $this->getBPrive())->toArray(),
            ...$this->getDocuments()->filter(fn (Document $document) => $document->getBPrive() !== $this->getBPrive())->toArray(),
            ],
        );
    }

    public function hasParentFolder(): bool
    {
        return null !== $this->getDossierParent();
    }

    public function move(?Dossier $parentFolder): void
    {
        $this->setDossierParent($parentFolder);

        if ($parentFolder && $parentFolder !== $this) {
            $parentFolder->addSousDossier($this);
            if ($parentFolder->getBPrive() !== $this->getBPrive()) {
                $this->toggleVisibility();
            }
        }
    }
}
