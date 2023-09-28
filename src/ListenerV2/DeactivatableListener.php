<?php

namespace App\ListenerV2;

use App\Entity\MembreCentre;
use App\Entity\User;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: MembreCentre::class)]
#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: MembreCentre::class)]
class DeactivatableListener
{
    use UserAwareTrait;

    public function __construct(private readonly Security $security)
    {
    }

    public function preUpdate(User $user, PreUpdateEventArgs $args): void
    {
        if (!$args->hasChangedField('enabled')) {
            return;
        }

        $user->isEnabled() ? $user->enable() : $user->disable($this->getUser());
    }

    public function prePersist(MembreCentre $userRelay, PrePersistEventArgs $args): void
    {
        $user = $userRelay->getMembre()?->getUser();

        if ($user && !$user->isEnabled()) {
            $user->enable();
            $args->getObjectManager()->flush();
        }
    }

    public function preRemove(MembreCentre $userRelay, PreRemoveEventArgs $args): void
    {
        $user = $userRelay->getMembre()?->getUser();

        if ($user && $user->isEnabled() && 1 === $user->getUserRelays()->count()) {
            $user->disable($this->getUser());
            $args->getObjectManager()->flush();
        }
    }
}
