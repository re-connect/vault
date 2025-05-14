<?php

namespace App\Event;

use App\Entity\Attributes\Gestionnaire;
use App\Entity\User;

class GestionnaireEvent extends REEvent
{
    public const GESTIONNAIRE_CREATED = 1;
    public const GESTIONNAIRE_MODIFIED = 2;

    protected $gestionnaire;
    protected $user;
    protected $context = [];

    public function __construct(Gestionnaire $gestionnaire, protected $type, ?User $user = null)
    {
        $this->gestionnaire = $gestionnaire;
        $this->user = $user;

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

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->getConstName($this->type);
    }
}
