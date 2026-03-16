<?php

namespace App\Event;

use App\Entity\Membre;
use App\Entity\User;

class MembreEvent extends REEvent
{
    public const MEMBRE_CREATED = 1;
    public const MEMBRE_MODIFIED = 2;
    protected $context = [];

    public function __construct(protected Membre $membre, protected $type, protected ?User $user = null)
    {
        $this->context = [
            'user_id' => $this->membre->getUser()->getId(),
            'by_user_id' => (null !== $this->user) ? $this->user->getId() : null,
        ];
    }

    public function getMembre(): Membre
    {
        return $this->membre;
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
