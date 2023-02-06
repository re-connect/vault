<?php

namespace App\Entity;

interface UserWithCentresInterface
{
    public function getUserCentre(Centre $centre);

    /**
     * @return UserCentre
     */
    public function getUsersCentres();

    public function isBeneficiaire();

    public function isMembre();

    public function getIsCreating();
}
