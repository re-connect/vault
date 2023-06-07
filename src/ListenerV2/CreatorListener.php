<?php

namespace App\ListenerV2;

use App\Entity\CreatorUser;
use App\Entity\DonneePersonnelle;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\SecurityBundle\Security;

class CreatorListener
{
    use UserAwareTrait;

    public function __construct(private Security $security)
    {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $user = $this->getUser();
        $entity = $args->getObject();

        if (!$entity instanceof DonneePersonnelle || !$user) {
            return;
        }

        $creator = (new CreatorUser())->setEntity($user);
        $entity->addCreator($creator);
    }
}
