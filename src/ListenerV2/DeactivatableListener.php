<?php

namespace App\ListenerV2;

use App\Entity\User;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Security;

class DeactivatableListener
{
    use UserAwareTrait;

    public function __construct(private Security $security)
    {
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        if (!($updatedUser = $args->getObject()) instanceof User || !$args->hasChangedField('enabled')) {
            return;
        }

        $updatedUser->isEnabled() ? $updatedUser->enable() : $updatedUser->disable($this->getUser());
    }
}
