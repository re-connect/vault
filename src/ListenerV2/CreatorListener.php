<?php

namespace App\ListenerV2;

use App\Entity\CreatorUser;
use App\Entity\DonneePersonnelle;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: DonneePersonnelle::class)]
class CreatorListener
{
    use UserAwareTrait;

    public function __construct(private readonly Security $security)
    {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function prePersist(DonneePersonnelle $personalData, LifecycleEventArgs $args): void
    {
        $user = $this->getUser();
        $personalData = $args->getObject();

        if (!$personalData instanceof DonneePersonnelle || !$user) {
            return;
        }

        $creator = (new CreatorUser())->setEntity($user);
        $personalData->addCreator($creator);
    }
}
