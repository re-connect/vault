<?php

namespace App\Listener;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: User::class)]
class UserListener
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function prePersist(User $user, LifecycleEventArgs $args): void
    {
        try {
            if (null === ($request = $this->requestStack->getCurrentRequest())) {
                throw new \RuntimeException('');
            }

            $user->setLastIp($request->getClientIp());
        } catch (\RuntimeException) {
            $user->setLastIp('127.0.0.1');
        }
    }
}
