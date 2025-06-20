<?php

namespace App\Event;

use App\Entity\Attributes\Centre;
use App\Entity\UserWithCentresInterface;

class CentreEvent extends REEvent
{
    public const CENTRE_USERWITHCENTRES_ASSOCIATED = 1;
    public const CENTRE_USERWITHCENTRES_DESASSOCIATED = 2;
    protected $user;

    public function __construct(protected Centre $centre, protected $type, protected UserWithCentresInterface $subject)
    {
        $this->context = [
            'user_id' => $this->subject->getUser()->getId(),
            'centre_id' => $this->centre->getId(),
        ];
    }

    public function getSubject(): UserWithCentresInterface
    {
        return $this->subject;
    }

    public function getCentre(): Centre
    {
        return $this->centre;
    }

    public function getType()
    {
        return $this->type;
    }
}
