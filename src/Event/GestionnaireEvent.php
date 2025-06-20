<?php

namespace App\Event;

use App\Entity\Attributes\Gestionnaire;
use App\Entity\Attributes\User;

class GestionnaireEvent extends REEvent
{
    public const GESTIONNAIRE_CREATED = 1;
    public const GESTIONNAIRE_MODIFIED = 2;
    protected $context = [];

    public function __construct(protected Gestionnaire $gestionnaire, protected $type, protected ?User $user = null)
    {
        $this->context = [
            'user_id' => $this->gestionnaire->getUser()->getId(),
            'by_user_id' => (null !== $this->user) ? $this->user->getId() : null,
        ];
    }

    public function getGestionnaire(): Gestionnaire
    {
        return $this->gestionnaire;
    }

    public function getUser(): ?User
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
