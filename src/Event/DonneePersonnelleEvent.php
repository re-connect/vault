<?php

namespace App\Event;

use App\Entity\Attributes\DonneePersonnelle;
use App\Entity\Attributes\User;
use Symfony\Component\Security\Core\User\UserInterface;

class DonneePersonnelleEvent extends REEvent
{
    public const DONNEEPERSONNELLE_CREATED = 1;
    public const DONNEEPERSONNELLE_MODIFIED = 2;
    public const DONNEEPERSONNELLE_DELETED = 3;
    public const DONNEEPERSONNELLE_SETPRIVE = 4;
    public const DONNEEPERSONNELLE_SETPUBLIC = 5;

    public function __construct(protected DonneePersonnelle $donneePersonnelle, protected ?UserInterface $user = null, protected $type = null)
    {
        $this->context = [
            'user_id' => $this->donneePersonnelle->getBeneficiaire()->getUser()->getId(),
            'by_user_id' => $this->user instanceof User ? $this->user->getId() : null,
            'entity_id' => $this->donneePersonnelle->getId(),
        ];

        $m = [];
        if (preg_match('#\\\\([a-zA-Z]+)$#', (string) $this->donneePersonnelle::class, $m)) {
            $this->context['entity'] = $m[1];
        }
    }

    public function getDonneePersonnelle(): DonneePersonnelle
    {
        return $this->donneePersonnelle;
    }

    public function getType()
    {
        return $this->type;
    }
}
