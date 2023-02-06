<?php

namespace App\Event;

use App\Entity\Membre;
use App\Entity\User;

class MembreEvent extends REEvent
{
    public const MEMBRE_CREATED = 1;
    public const MEMBRE_MODIFIED = 2;

    protected $membre;
    protected $user;
    protected $type;
    protected $context = [];

    public function __construct(Membre $membre, $type, User $user = null)
    {
        $this->membre = $membre;
        $this->user = $user;
        $this->type = $type;

        $this->context = [
            'user_id' => $this->membre->getUser()->getId(),
            'by_user_id' => (null !== $this->user) ? $this->user->getId() : null,
        ];
    }

    public function getMembre()
    {
        return $this->membre;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getType()
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return $this->getConstName($this->type);
    }
}
