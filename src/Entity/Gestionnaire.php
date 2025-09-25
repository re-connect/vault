<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'gestionnaire')]
#[ORM\Index(columns: ['association_id'], name: 'IDX_F4461B20EFB9C8A5')]
#[ORM\UniqueConstraint(name: 'UNIQ_F4461B20A76ED395', columns: ['user_id'])]
class Gestionnaire extends Subject implements UserHandleCentresInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'gestionnaire', targetEntity: Centre::class)]
    private Collection $centres;

    #[ORM\ManyToOne(targetEntity: \Association::class, inversedBy: 'gestionnaire')]
    #[ORM\JoinColumn(name: 'association_id', referencedColumnName: 'id', nullable: false)]
    private ?Association $association = null;

    #[ORM\OneToMany(mappedBy: 'entity', targetEntity: ClientGestionnaire::class, orphanRemoval: true)]
    private Collection $externalLinks;

    #[ORM\OneToOne(inversedBy: 'subjectGestionnaire', targetEntity: User::class, cascade: ['persist', 'remove'])]
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

    public function __construct()
    {
        $this->centres = new ArrayCollection();
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
        $this->user->setTypeUser(User::USER_TYPE_GESTIONNAIRE);

        return $this;
    }

    public function addCentre(Centre $centre): static
    {
        $this->centres[] = $centre;
        $centre->setGestionnaire($this);

        return $this;
    }

    public function removeCentre(Centre $centre): void
    {
        $centre->setGestionnaire();
        $this->centres->removeElement($centre);
    }

    public function getAssociation(): ?Association
    {
        return $this->association;
    }

    public function setAssociation(?Association $association = null): static
    {
        $this->association = $association;

        return $this;
    }

    /**
     * @return Collection<int, Centre>
     */
    #[\Override]
    public function getHandledCentres(): Collection
    {
        return $this->centres;
    }

    public function getCentresToString(): string
    {
        /** @var Centre[]|Collection<Centre> $centres */
        $str = '';
        $centres = $this->getCentres();
        foreach ($centres as $centre) {
            $str .= $centre->getNom();
            if ($centres->last() !== $centre) {
                $str .= ' / ';
            }
        }

        return $str;
    }

    public function getCentresIds(): string
    {
        /** @var Centre[]|Collection<Centre> $centres */
        $str = '';
        $centres = $this->getCentres();
        foreach ($centres as $centre) {
            $str .= $centre->getId();
            if ($centres->last() !== $centre) {
                $str .= ' / ';
            }
        }

        return $str;
    }

    /**
     * @return Collection<int, Centre>
     */
    public function getCentres(): Collection
    {
        return $this->centres;
    }

    public function setCentres($centres): void
    {
        if (!is_array($centres)) {
            $ar = [];
            $ar[] = $centres;
            $centres = $ar;
        }
        $this->centres = $centres;
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
    public function jsonSerialize(bool $withUser = true): array
    {
        $data = [
            'id' => $this->id,
            'centres' => $this->getCentreNoms()->toArray(),
            'created_at' => $this->createdAt->format(\DateTimeInterface::W3C),
            'updated_at' => $this->createdAt->format(\DateTimeInterface::W3C),
        ];
        if ($withUser) {
            $data['user'] = $this->user;
        }

        return $data;
    }

    public function getCentreNoms(): ArrayCollection
    {
        $centres = new ArrayCollection();
        if (null !== $this->centres) {
            foreach ($this->centres as $item) {
                $centres->add($item->getNom());
            }
        }

        return $centres;
    }

    public function jsonSerializeAPI(): array
    {
        return [
            'subject_id' => $this->id,
            'centres' => $this->getCentreNoms()->toArray(),
        ];
    }

    public function addExternalLink(ClientGestionnaire $externalLink): static
    {
        $this->externalLinks[] = $externalLink;
        $externalLink->setEntity($this);

        return $this;
    }

    public function removeExternalLink(ClientGestionnaire $externalLink): bool
    {
        return $this->externalLinks->removeElement($externalLink);
    }

    public function getExternalLinks(): Collection
    {
        return $this->externalLinks;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->association = null;
            $this->externalLinks = new ArrayCollection();

            $this->user = clone $this->user;

            // Centre
            /** @var Centre[] $centres */
            $centres = [];
            foreach ($this->centres as $centre) {
                $this->removeCentre($centre);
                $centres[] = $centre;
                $centre->setGestionnaire($this);
            }
            $this->centres = [];

            foreach ($centres as $centre) {
                $this->addCentre(clone $centre);
            }
        }
    }
}
