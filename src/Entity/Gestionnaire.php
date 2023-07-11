<?php

namespace App\Entity;

use App\Traits\GedmoTimedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Gestionnaire extends Subject implements UserHandleCentresInterface
{
    use GedmoTimedTrait;

    /**
     * @var Centre[]|Collection<Centre>
     */
    private $centres;
    private ?Association $association;
    /**
     * @var array|Collection
     */
    private $externalLinks;

    public function __construct()
    {
        $this->centres = new ArrayCollection();
        $this->externalLinks = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function setUser(User $user = null): self
    {
        $this->user = $user;
        $this->user->setTypeUser(User::USER_TYPE_GESTIONNAIRE);

        return $this;
    }

    public function addCentre(Centre $centre): self
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

    public function setAssociation(Association $association = null): self
    {
        $this->association = $association;

        return $this;
    }

    /**
     * @return Centre[]|Collection<Centre>
     */
    public function getHandledCentres()
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

    public function getCentres()
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

    public function addExternalLink(ClientGestionnaire $externalLink): Gestionnaire
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
            $this->externalLinks = [];

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
