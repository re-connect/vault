<?php

namespace App\ListenerV2;

use App\Entity\User;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

class DeactivatableListener
{
    use UserAwareTrait;

    public function __construct(private readonly Security $security)
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
