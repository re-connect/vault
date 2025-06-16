<?php

namespace App\Entity;

use App\Entity\Attributes\Centre;
use App\Entity\Attributes\UserCentre;
use Doctrine\Common\Collections\Collection;

interface UserWithCentresInterface
{
    public function getUserCentre(Centre $centre);

    public function getUsersCentres();

    /** @return Collection<int, UserCentre> */
    public function getUserCentres(): Collection;

    public function isBeneficiaire();

    public function isMembre();

    public function getUserCentresCount();
}
