<?php

namespace App\ListenerV2;

use App\Entity\CreatorUser;
use App\Entity\DonneePersonnelle;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Security;

class CreatorListener
{
    use UserAwareTrait;

    public function __construct(private Security $security)
    {
    }

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
