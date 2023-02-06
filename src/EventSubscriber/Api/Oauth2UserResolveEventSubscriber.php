<?php

namespace App\EventSubscriber\Api;

use App\Repository\UserRepository;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Oauth2UserResolveEventSubscriber implements EventSubscriberInterface
{
    private UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function onUserResolve(UserResolveEvent $event): UserResolveEvent
    {
        $user = $this->repository->loadUserByIdentifier($event->getUsername());
        $event->setUser($user);

        return $event;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'league.oauth2_server.event.user_resolve' => 'onUserResolve',
        ];
    }
}
