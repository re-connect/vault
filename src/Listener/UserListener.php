<?php

namespace App\Listener;

use App\Entity\User;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;

class UserListener
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // only act on some "User" entity
        if (!$entity instanceof User) {
            return;
        }
//        $entityManager = $args->getEntityManager();
        try {
            if (null === ($request = $this->requestStack->getCurrentRequest())) {
                throw new \RuntimeException('');
            }

            $entity->setLastIp($request->getClientIp());
        } catch (\RuntimeException $e) {
            $entity->setLastIp('127.0.0.1');
        }
    }
}
