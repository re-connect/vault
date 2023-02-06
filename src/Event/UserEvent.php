<?php

namespace App\Event;

use App\Entity\Client;
use App\Entity\User;

final class UserEvent extends REEvent
{
    public const BENEFICIAIRE_CONNECTION = 1;
    public const MEMBRE_CONNECTION = 2;
    public const GESTIONNAIRE_CONNECTION = 3;
    public const ASSOCIATION_CONNECTION = 4;
    public const ADMINISTRATEUR_CONNECTION = 5;

    private $user;
    protected $type;

    public function __construct(User $user, bool $firstConnectionToday, Client $client = null)
    {
        if ($user->isBeneficiaire()) {
            $this->type = self::BENEFICIAIRE_CONNECTION;
        } elseif ($user->isMembre()) {
            $this->type = self::MEMBRE_CONNECTION;
        } elseif ($user->isGestionnaire()) {
            $this->type = self::GESTIONNAIRE_CONNECTION;
        } elseif ($user->isAssociation()) {
            $this->type = self::ASSOCIATION_CONNECTION;
        } elseif ($user->isAdministrateur()) {
            $this->type = self::ADMINISTRATEUR_CONNECTION;
        }

        $this->user = $user;

        $this->context['user_id'] = $user->getId();
        $this->context['first_connection_today'] = $firstConnectionToday;
        $this->context['centres'] = $user->getCentresToString();

        if (null !== $client) {
            $this->context['client_id'] = $client->getId();
        }
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return $this->getConstName($this->type);
    }
}
