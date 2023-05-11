<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Traits\GedmoTimedTrait;
use App\Validator\Constraints\UniqueExternalLink;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MembreRepository")
 */
#[ApiResource(
    shortName: 'pro',
    operations: [new Get(), new GetCollection(), new Post(), new Put(), new Patch(), new Delete()],
    normalizationContext: ['groups' => ['v3:member:read', 'v3:user:read']],
    denormalizationContext: ['groups' => ['v3:member:write', 'v3:user:write']],
    openapiContext: ['tags' => ['Professionnels']],
    security: "is_granted('ROLE_OAUTH2_PROS')",
)]
class Membre extends Subject implements UserWithCentresInterface, UserHandleCentresInterface
{
    use GedmoTimedTrait;

    /**
     * @var Collection|MembreCentre[]
     */
    #[Groups(['v3:member:read'])]
    private $membresCentres;
    /** @var string */
    private $activationSmsCode;
    /** @var \DateTime */
    private $activationSmsCodeLastSend;
    /** @var Collection */
    private $consultationsBeneficiaires;
    /** @var Collection */
    private $evenements;
    /** @var Collection */
    #[UniqueExternalLink]
    private $externalLinks;
    private ?bool $wasGestionnaire = false;

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

    /**
     * Set user.
     *
     * @return Membre
     */
    public function setUser(?User $user = null)
    {
        $this->user = $user;
        $this->user->setTypeUser(User::USER_TYPE_MEMBRE);

        return $this;
    }

    /**
     * Add membresCentres.
     *
     * @return Membre
     */
    public function addMembresCentre(MembreCentre $membresCentres)
    {
        $this->membresCentres[] = $membresCentres;
        $membresCentres->setMembre($this);

        return $this;
    }

    /**
     * @return $this
     */
    public function addBeneficiaire(Beneficiaire $beneficiaire, Centre $centre)
    {
        $centre->addBeneficiaire($beneficiaire);

        return $this;
    }

    /**
     * Remove membresCentres.
     */
    public function removeMembresCentre(MembreCentre $membresCentres)
    {
        $this->membresCentres->removeElement($membresCentres);
    }

    /**
     * Get activationSmsCode.
     *
     * @return string
     */
    public function getActivationSmsCode()
    {
        return $this->activationSmsCode;
    }

    /**
     * Set activationSmsCode.
     *
     * @param string $activationSmsCode
     *
     * @return Membre
     */
    public function setActivationSmsCode($activationSmsCode)
    {
        $this->activationSmsCode = $activationSmsCode;

        return $this;
    }

    /**
     * Get activationSmsCodeLastSend.
     *
     * @return \DateTime
     */
    public function getActivationSmsCodeLastSend()
    {
        return $this->activationSmsCodeLastSend;
    }

    /**
     * Set activationSmsCodeLastSend.
     *
     * @param \DateTime $activationSmsCodeLastSend
     *
     * @return Membre
     */
    public function setActivationSmsCodeLastSend($activationSmsCodeLastSend)
    {
        $this->activationSmsCodeLastSend = $activationSmsCodeLastSend;

        return $this;
    }

    /**
     * Get isCreating.
     *
     * @return bool
     */
    public function getIsCreating()
    {
        return false;
    }

    public function getUserCentre(Centre $centre)
    {
        foreach ($this->getMembresCentres() as $membreCentre) {
            if ($membreCentre->getCentre() == $centre) {
                return $membreCentre;
            }
        }

        return null;
    }

    /**
     * Get membresCentres.
     *
     * @return MembreCentre[]|ArrayCollection
     */
    public function getMembresCentres()
    {
        return $this->membresCentres;
    }

    public function isBeneficiaire()
    {
        return false;
    }

    public function isMembre()
    {
        return true;
    }

    /**
     * @return ArrayCollection|Centre[]
     */
    public function getHandledCentres()
    {
        $arRet = new ArrayCollection();
        foreach ($this->getUsersCentres() as $userCentre) {
            $arRet->add($userCentre->getCentre());
        }

        return $arRet;
    }

    /**
     * @return MembreCentre[]|UserCentre|ArrayCollection|Collection
     */
    public function getUsersCentres()
    {
        return $this->getMembresCentres();
    }

    /**
     * Add consultationsBeneficiaire.
     *
     * @return Membre
     */
    public function addConsultationsBeneficiaire(ConsultationBeneficiaire $consultationsBeneficiaire)
    {
        $this->consultationsBeneficiaires[] = $consultationsBeneficiaire;

        return $this;
    }

    /**
     * Remove consultationsBeneficiaire.
     */
    public function removeConsultationsBeneficiaire(ConsultationBeneficiaire $consultationsBeneficiaire)
    {
        $this->consultationsBeneficiaires->removeElement($consultationsBeneficiaire);
    }

    /**
     * Get consultationsBeneficiaires.
     *
     * @return Collection
     */
    public function getConsultationsBeneficiaires()
    {
        return $this->consultationsBeneficiaires;
    }

    /**
     * Add evenement.
     *
     * @return Membre
     */
    public function addEvenement(Evenement $evenement)
    {
        $this->evenements[] = $evenement;

        return $this;
    }

    /**
     * Remove evenement.
     */
    public function removeEvenement(Evenement $evenement)
    {
        $this->evenements->removeElement($evenement);
    }

    /**
     * Get evenements.
     *
     * @return Collection
     */
    public function getEvenements()
    {
        return $this->evenements;
    }

    /**
     * Get beneficiairesCentres.
     *
     * @return string
     */
    public function getCentresToString()
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
     * Retourne un tableau des centres.
     *
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
     *
     * @return ArrayCollection
     */
    public function getCentreNoms()
    {
        $centres = new ArrayCollection();
        if (null !== $this->membresCentres) {
            foreach ($this->membresCentres as $membresCentre) {
                $centres->add($membresCentre->getCentre()->getNom());
            }
        }

        return $centres;
    }

    public function jsonSerializeAPI()
    {
        $data = ['subject_id' => $this->id, 'membres_centres' => $this->getCentreNoms()->toArray()];

        return $data;
    }

    /**
     * Add externalLink.
     *
     * @return Membre
     */
    public function addExternalLink(ClientMembre $externalLink)
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
     * Get externalLinks.
     *
     * @return Collection
     */
    public function getExternalLinks()
    {
        return $this->externalLinks;
    }

    /**
     * @return $this
     */
    public function addCreator(Creator $creator)
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
    public function getAffiliatedRelaysWithBeneficiaryManagement(): Collection
    {
        return $this->getMembresCentres()
            ->filter(
                fn (MembreCentre $professionalRelay) => true === $professionalRelay->getBValid() && $professionalRelay->canManageBeneficiaries())
            ->map(
                fn (MembreCentre $professionalRelay) => $professionalRelay->getCentre(),
            );
    }

    /**
     * @return Collection <int, Centre>
     */
    public function getManageableRelays(Beneficiaire $beneficiary): Collection
    {
        return $this->getAffiliatedRelaysWithBeneficiaryManagement()->filter(fn (Centre $relay) => $beneficiary->getAffiliatedRelays()->contains($relay));
    }

    public function countManageableRelays(Beneficiaire $beneficiary): int
    {
        return $this->getManageableRelays($beneficiary)->count();
    }

    public function hasOneManageableRelay(Beneficiaire $beneficiary): bool
    {
        return 1 === $this->countManageableRelays($beneficiary);
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
