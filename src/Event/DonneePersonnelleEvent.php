<?php

namespace App\Event;

use App\Entity\DonneePersonnelle;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class DonneePersonnelleEvent extends REEvent
{
    public const DONNEEPERSONNELLE_CREATED = 1;
    public const DONNEEPERSONNELLE_MODIFIED = 2;
    public const DONNEEPERSONNELLE_DELETED = 3;
    public const DONNEEPERSONNELLE_SETPRIVE = 4;
    public const DONNEEPERSONNELLE_SETPUBLIC = 5;

    protected $donneePersonnelle;
    protected $type;
    protected $user;

    public function __construct(DonneePersonnelle $donneePersonnelle, UserInterface $user = null, $type = null)
    {
        $this->donneePersonnelle = $donneePersonnelle;
        $this->type = $type;
        $this->user = $user;

        $this->context = [
            'user_id' => $donneePersonnelle->getBeneficiaire()->getUser()->getId(),
            'by_user_id' => $user instanceof User ? $user->getId() : null,
            'entity_id' => $donneePersonnelle->getId(),
        ];

        $m = [];
        if (preg_match('#\\\\([a-zA-Z]+)$#', get_class($this->donneePersonnelle), $m)) {
            $this->context['entity'] = $m[1];
        }
    }

    public function getDonneePersonnelle()
    {
        return $this->donneePersonnelle;
    }

    public function getType()
    {
        return $this->type;
    }
}
