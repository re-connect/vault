<?php

namespace App\EventSubscriber\Api;

use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;

class Oauth2UserResolveSubscriber implements EventSubscriberInterface
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function checkUserPasswordValidity(UserResolveEvent $event): UserResolveEvent
    {
        $user = $event->getUser();
        $password = $event->getPassword();

        if ($user instanceof LegacyPasswordAuthenticatedUserInterface && !$this->hasher->isPasswordValid($user, $password)) {
            $event->setUser(null);
        }

        return $event;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'league.oauth2_server.event.user_resolve' => 'checkUserPasswordValidity',
        ];
    }
}
