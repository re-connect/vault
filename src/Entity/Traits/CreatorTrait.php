<?php

namespace App\Entity\Traits;

use App\Entity\Creator;
use App\Entity\CreatorCentre;
use App\Entity\CreatorClient;
use App\Entity\CreatorUser;
use Doctrine\Common\Collections\Collection;

trait CreatorTrait
{
    protected Collection $creators;

    public function addCreator(Creator $creator): static
    {
        $this->creators->add($creator);
        $creator->setPersonalData($this);

        return $this;
    }

    public function removeCreator(Creator $creator): bool
    {
        return $this->creators->removeElement($creator);
    }

    /**
     * @return Collection<int, Creator>
     */
    public function getCreators(): Collection
    {
        return $this->creators;
    }

    public function getCreatorUser(): ?CreatorUser
    {
        $creator = $this->creators->filter(static function ($creator) {
            return $creator instanceof CreatorUser;
        })->first();

        return false === $creator ? null : $creator;
    }

    public function getCreatorCentre(): ?CreatorCentre
    {
        $creator = $this->creators->filter(static function ($creator) {
            return $creator instanceof CreatorCentre;
        })->first();

        return false === $creator ? null : $creator;
    }

    public function getCreatorClient(): ?CreatorClient
    {
        $creator = $this->creators->filter(static function ($creator) {
            return $creator instanceof CreatorClient;
        })->first();

        return false === $creator ? null : $creator;
    }

    public function getCreatorUserFullName(): string
    {
        return $this->getCreatorUser()?->getEntity()?->getFullName() ?? '';
    }
}
