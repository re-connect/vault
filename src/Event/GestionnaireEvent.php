<?php

namespace App\Event;

use App\Entity\Gestionnaire;
use App\Entity\User;

class GestionnaireEvent extends REEvent
{
    public const GESTIONNAIRE_CREATED = 1;
    public const GESTIONNAIRE_MODIFIED = 2;

    protected $gestionnaire;
    protected $user;
    protected $type;
    protected $context = [];

    public function __construct(Gestionnaire $gestionnaire, $type, User $user = null)
    {
        $this->gestionnaire = $gestionnaire;
        $this->user = $user;
        $this->type = $type;

        $this->context = [
            'user_id' => $this->gestionnaire->getUser()->getId(),
            'by_user_id' => (null !== $this->user) ? $this->user->getId() : null,
        ];
    }

    public function getGestionnaire()
    {
        return $this->gestionnaire;
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
