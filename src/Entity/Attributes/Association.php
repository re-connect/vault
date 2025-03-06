<?php

namespace App\Entity\Attributes;

use App\Entity\Gestionnaire;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity]
#[ORM\Table(name: 'association')]
#[ORM\UniqueConstraint(name: 'UNIQ_FD8521CCA76ED395', columns: ['user_id'])]
class Association extends Subject
{
    use TimestampableEntity;

    public const ASSOCIATION_CATEGORIEJURIDIQUE_ASSOCIATION = 'association';
    public const ASSOCIATION_CATEGORIEJURIDIQUE_CCAS = 'ccas';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 255, nullable: false)]
    private string $nom;

    #[ORM\Column(name: 'categorieJuridique', type: 'string', length: 255, nullable: true)]
    private ?string $categorieJuridique = null;

    #[ORM\Column(name: 'siren', type: 'string', length: 255, nullable: true)]
    private ?string $siren = null;

    #[ORM\Column(name: 'urlSite', type: 'string', length: 255, nullable: true)]
    private ?string $urlSite = null;

    #[ORM\OneToOne(inversedBy: 'subjectAssociation', targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'association', targetEntity: Gestionnaire::class)]
    private Collection $gestionnaires;

    #[ORM\OneToMany(mappedBy: 'association', targetEntity: Centre::class)]
    private Collection $centres;

    public function __construct()
    {
        $this->gestionnaires = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public static function getAllCategories()
    {
        return [
            self::ASSOCIATION_CATEGORIEJURIDIQUE_ASSOCIATION => 'association',
            self::ASSOCIATION_CATEGORIEJURIDIQUE_CCAS => 'communal_centre_for_social_action',
        ];
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getUrlSite(): ?string
    {
        return $this->urlSite;
    }

    public function setUrlSite(string $urlSite): static
    {
        $this->urlSite = $urlSite;

        return $this;
    }

    public function getCategorieJuridique(): ?string
    {
        return $this->categorieJuridique;
    }

    public function setCategorieJuridique(string $categorieJuridique): static
    {
        $this->categorieJuridique = $categorieJuridique;

        return $this;
    }

    #[\Override]
    public function setUser(?User $user = null): static
    {
        $this->user = $user;
        $this->user->setTypeUser(User::USER_TYPE_ASSOCIATION);

        return $this;
    }

    /**
     * @return Collection|Gestionnaire[]
     */
    public function getGestionnaires(): Collection
    {
        return $this->gestionnaires;
    }

    #[\Override]
    public function __toString(): string
    {
        if (!empty($this->getNom())) {
            return $this->getNom();
        }

        return '';
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
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
    #[\Override]
    public function jsonSerialize(): array
    {
        return [];
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $gestionnaires = [];
            foreach ($this->gestionnaires as $gestionnaire) {
                $gestionnaires[] = clone $gestionnaire;
                $this->removeGestionnaire($gestionnaire);
            }

            foreach ($gestionnaires as $gestionnaire) {
                $this->addGestionnaire($gestionnaire);
            }

            $this->user = clone $this->user;
        }
    }

    public function removeGestionnaire(Gestionnaire $gestionnaire): void
    {
        $this->gestionnaires->removeElement($gestionnaire);

        if ($this->gestionnaires->contains($gestionnaire)) {
            $this->gestionnaires->removeElement($gestionnaire);
            if ($gestionnaire->getAssociation() === $this) {
                $gestionnaire->setAssociation();
            }
        }
    }

    public function addGestionnaire(Gestionnaire $gestionnaires): static
    {
        $this->gestionnaires[] = $gestionnaires;
        $gestionnaires->setAssociation($this);

        return $this;
    }

    public function getCentre(): Collection
    {
        return $this->centres;
    }

    public function addCentre(Centre $centre): static
    {
        $this->centres[] = $centre;
        $centre->setAssociation($this);

        return $this;
    }

    #[\Override]
    public function getDefaultUsername(): string
    {
        return (new AsciiSlugger())->slug($this->nom)->replaceMatches("#[ \'-]#", '')->lower()->toString();
    }
}
