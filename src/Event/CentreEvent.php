<?php

namespace App\Event;

use App\Entity\Centre;
use App\Entity\UserWithCentresInterface;

class CentreEvent extends REEvent
{
    public const CENTRE_USERWITHCENTRES_ASSOCIATED = 1;
    public const CENTRE_USERWITHCENTRES_DESASSOCIATED = 2;

    protected $subject;
    protected $user;
    protected $type;
    protected $centre;

    public function __construct(Centre $centre, $type, UserWithCentresInterface $subject)
    {
        $this->centre = $centre;
        $this->subject = $subject;
        $this->type = $type;

        $this->context = [
            'user_id' => $subject->getUser()->getId(),
            'centre_id' => $centre->getId(),
        ];
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getCentre()
    {
        return $this->centre;
    }

    public function getType()
    {
        return $this->type;
    }
}
