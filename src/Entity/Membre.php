<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use App\Repository\MembreRepository;
use App\Validator\Constraints as CustomAssert;
use App\Validator\Constraints\UniqueExternalLink;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MembreRepository::class)]
#[ORM\Table(name: 'membre')]
#[ORM\UniqueConstraint(name: 'UNIQ_F6B4FB29A76ED395', columns: ['user_id'])]
#[ApiResource(
    shortName: 'pro',
    operations: [new Get(), new GetCollection(), new Post(), new Put(), new Patch(), new Delete()],
    normalizationContext: ['groups' => ['v3:member:read', 'v3:user:read']],
    denormalizationContext: ['groups' => ['v3:member:write', 'v3:user:write']],
    openapi: new Operation(
        tags: ['Professionnels'],
    ),
    security: "is_granted('ROLE_OAUTH2_PROS')",
)]
class Membre extends Subject implements UserWithCentresInterface, UserHandleCentresInterface
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Groups(['v3:member:read'])]
    #[CustomAssert\RelayUnique]
    #[ORM\OneToMany(mappedBy: 'membre', targetEntity: MembreCentre::class, cascade: ['persist', 'remove'])]
    private Collection $membresCentres;

    #[ORM\Column(name: 'activationSmsCode', type: 'string', length: 255, nullable: true)]
    private ?string $activationSmsCode = null;

    private \DateTime $activationSmsCodeLastSend;

    #[ORM\OneToMany(mappedBy: 'membre', targetEntity: ConsultationBeneficiaire::class, cascade: ['remove'])]
    private Collection $consultationsBeneficiaires;

    #[ORM\OneToMany(mappedBy: 'membre', targetEntity: Evenement::class, cascade: ['persist'])]
    private Collection $evenements;

    #[ORM\OneToMany(mappedBy: 'entity', targetEntity: ClientMembre::class, cascade: ['persist', 'remove'])]
    #[UniqueExternalLink]
    private Collection $externalLinks;

    #[ORM\Column(name: 'wasGestionnaire', type: 'boolean', nullable: false, options: ['default' => false])]
    private ?bool $wasGestionnaire = false;

    #[ORM\Column(name: 'usesRosalie', type: 'boolean', nullable: false, options: ['default' => false])]
    private ?bool $usesRosalie = false;

    #[ORM\OneToOne(inversedBy: 'subjectMembre', targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected ?User $user = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $updatedAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->membresCentres = new ArrayCollection();
        $this->consultationsBeneficiaires = new ArrayCollection();
        $this->externalLinks = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    #[\Override]
    public function setUser(?User $user = null): static
    {
        $this->user = $user;
        $this->user->setTypeUser(User::USER_TYPE_MEMBRE);
        $this->user->setSubjectMembre($this);

        return $this;
    }

    public function addMembresCentre(MembreCentre $membresCentres): static
    {
        $this->membresCentres[] = $membresCentres;
        $membresCentres->setMembre($this);

        return $this;
    }

    public function addBeneficiaire(Beneficiaire $beneficiaire, Centre $centre): static
    {
        $centre->addBeneficiaire($beneficiaire);

        return $this;
    }

    public function removeMembresCentre(MembreCentre $membresCentres): void
    {
        $this->membresCentres->removeElement($membresCentres);
    }

    public function getActivationSmsCode(): string
    {
        return $this->activationSmsCode;
    }

    public function setActivationSmsCode(?string $activationSmsCode): static
    {
        $this->activationSmsCode = $activationSmsCode;

        return $this;
    }

    public function getActivationSmsCodeLastSend(): \DateTime
    {
        return $this->activationSmsCodeLastSend;
    }

    public function setActivationSmsCodeLastSend(\DateTime $activationSmsCodeLastSend): static
    {
        $this->activationSmsCodeLastSend = $activationSmsCodeLastSend;

        return $this;
    }

    /** @return Collection<int, MembreCentre> */
    #[\Override]
    public function getUserCentres(): Collection
    {
        return $this->getMembresCentres();
    }

    #[\Override]
    public function getUserCentresCount(): int
    {
        return $this->getMembresCentres()->count();
    }

    #[\Override]
    public function getUserCentre(Centre $centre): ?MembreCentre
    {
        foreach ($this->getMembresCentres() as $membreCentre) {
            if ($membreCentre->getCentre() == $centre) {
                return $membreCentre;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, MembreCentre>
     */
    public function getMembresCentres(): Collection
    {
        return $this->membresCentres;
    }

    #[\Override]
    public function isBeneficiaire(): bool
    {
        return false;
    }

    #[\Override]
    public function isMembre(): bool
    {
        return true;
    }

    /**
     * @return ArrayCollection|Centre[]
     */
    #[\Override]
    public function getHandledCentres(): ArrayCollection
    {
        $arRet = new ArrayCollection();
        foreach ($this->getUsersCentres() as $userCentre) {
            $arRet->add($userCentre->getCentre());
        }

        return $arRet;
    }

    #[\Override]
    public function getUsersCentres(): Collection
    {
        return $this->getMembresCentres();
    }

    public function addConsultationsBeneficiaire(ConsultationBeneficiaire $consultationsBeneficiaire): static
    {
        $this->consultationsBeneficiaires[] = $consultationsBeneficiaire;

        return $this;
    }

    public function removeConsultationsBeneficiaire(ConsultationBeneficiaire $consultationsBeneficiaire): void
    {
        $this->consultationsBeneficiaires->removeElement($consultationsBeneficiaire);
    }

    /**
     * @return Collection<int, ConsultationBeneficiaire>
     */
    public function getConsultationsBeneficiaires(): Collection
    {
        return $this->consultationsBeneficiaires;
    }

    public function addEvenement(Evenement $evenement): static
    {
        $this->evenements[] = $evenement;

        return $this;
    }

    public function removeEvenement(Evenement $evenement): void
    {
        $this->evenements->removeElement($evenement);
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        return $this->evenements;
    }

    public function getCentresToString(): string
    {
        /** @var MembreCentre[] $centres */
        $str = '';
        $centres = $this->getMembresCentres();
        foreach ($centres as $key => $centre) {
            $str .= $centre->getCentre()->getNom();
            if ($centres->last() !== $centre) {
                $str .= ' / ';
            }
        }

        return $str;
    }

    /**
     * @return Centre[]|ArrayCollection
     */
    public function getCentres()
    {
        /** @var Centre[] $centres */
        $centres = new ArrayCollection();
        foreach ($this->getMembresCentres() as $membresCentre) {
            $centres->add($membresCentre->getCentre());
        }

        return $centres;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @param bool $withUser
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    #[\Override]
    public function jsonSerialize($withUser = true): array
    {
        $data = [
            'id' => $this->id,
            'membres_centres' => $this->membresCentres->toArray(),
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->createdAt->format(\DateTime::W3C),
        ];
        if ($withUser) {
            $data['user'] = $this->user;
        }

        return $data;
    }

    /**
     * Retourne les centres.
     */
    public function getCentreNoms(): ArrayCollection
    {
        $centres = new ArrayCollection();
        if (null !== $this->membresCentres) {
            foreach ($this->membresCentres as $membresCentre) {
                $centres->add($membresCentre->getCentre()->getNom());
            }
        }

        return $centres;
    }

    public function jsonSerializeAPI(): array
    {
        $data = ['subject_id' => $this->id, 'membres_centres' => $this->getCentreNoms()->toArray()];

        return $data;
    }

    public function addExternalLink(ClientMembre $externalLink): static
    {
        $this->externalLinks[] = $externalLink;
        $externalLink->setEntity($this);

        return $this;
    }

    /**
     * Remove externalLink.
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeExternalLink(ClientMembre $externalLink)
    {
        return $this->externalLinks->removeElement($externalLink);
    }

    /**
     * @return Collection<int, ClientMembre>
     */
    public function getExternalLinks(): Collection
    {
        return $this->externalLinks;
    }

    public function addCreator(Creator $creator): static
    {
        $this->user->addCreator($creator);

        return $this;
    }

    public function getRegionToString(): string
    {
        return implode(', ', array_unique(array_map(fn (Centre $centre) => $centre?->getRegion(), $this->getCentres()->toArray())));
    }

    /**
     * @return Collection <int, Centre>
     */
    public function getAffiliatedRelays(): ReadableCollection
    {
        return $this->getMembresCentres()
            ->filter(
                fn (MembreCentre $professionalRelay) => $professionalRelay->getBValid(),
            )
            ->map(
                fn (MembreCentre $professionalRelay) => $professionalRelay->getCentre(),
            );
    }

    /**
     * @return Collection <int, Centre>
     */
    public function getAffiliatedRelaysWithBeneficiaryManagement(): Collection
    {
        return $this->getMembresCentres()
            ->filter(
                fn (MembreCentre $professionalRelay) => $professionalRelay->getBValid() && $professionalRelay->canManageBeneficiaries())
            ->map(
                fn (MembreCentre $professionalRelay) => $professionalRelay->getCentre(),
            );
    }

    /**
     * @return Collection <int, Centre>
     */
    public function getAffiliatedRelaysWithProfessionalManagement(): Collection
    {
        return $this->getMembresCentres()
            ->filter(
                fn (MembreCentre $professionalRelay) => $professionalRelay->getBValid() && $professionalRelay->canManageProfessionals())
            ->map(
                fn (MembreCentre $professionalRelay) => $professionalRelay->getCentre(),
            );
    }

    /**
     * @return ReadableCollection <int, Centre>
     */
    public function getManageableRelays(User $user): ReadableCollection
    {
        if ($user->isBeneficiaire()) {
            return $this->getAffiliatedRelaysWithBeneficiaryManagement()->filter(fn (Centre $relay) => $user->getAffiliatedRelays()->contains($relay));
        } elseif ($user->isMembre()) {
            return $this->getAffiliatedRelaysWithProfessionalManagement()->filter(fn (Centre $relay) => $user->getAffiliatedRelays()->contains($relay));
        }

        return new ArrayCollection();
    }

    public function wasGestionnaire(): ?bool
    {
        return $this->wasGestionnaire;
    }

    public function setWasGestionnaire(?bool $wasGestionnaire): self
    {
        $this->wasGestionnaire = $wasGestionnaire;

        return $this;
    }

    public function usesRosalie(): ?bool
    {
        return $this->usesRosalie;
    }

    public function setUsesRosalie(?bool $usesRosalie): Membre
    {
        $this->usesRosalie = $usesRosalie;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->consultationsBeneficiaires = [];
            $this->evenements = [];
            $this->externalLinks = [];
            $this->membresCentres = [];
            $this->user = clone $this->user;
        }
    }
}
