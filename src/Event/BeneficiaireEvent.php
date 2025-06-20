<?php

namespace App\Event;

use App\Entity\Beneficiaire;
use App\Entity\User;

class BeneficiaireEvent extends REEvent
{
    public const BENEFICIAIRE_FOLDER_OPENED = 1;
    public const BENEFICIAIRE_FOLDER_CLOSED = 2;
    public const BENEFICIAIRE_CREATED = 3;
    public const BENEFICIAIRE_MODIFIED = 4;
    public const BENEFICIAIRE_DELETED = 5;
    public const BENEFICIAIRE_FOLDER_OPENED_ERROR = 6;
    public const BENEFICIAIRE_FOLDER_CLOSED_ERROR = 7;

    public function __construct(protected Beneficiaire $beneficiaire, protected $type, protected ?User $user = null)
    {
        $this->context = [
            'user_id' => $this->beneficiaire->getUser()->getId(),
            'by_user_id' => (null !== $this->user) ? $this->user->getId() : null,
        ];
    }

    public function getBeneficiaire(): Beneficiaire
    {
        return $this->beneficiaire;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getType()
    {
        return $this->type;
    }
}
