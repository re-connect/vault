<?php

namespace App\EventSubscriber\Api;

use App\Repository\UserRepository;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;

#[AsEventListener(OAuth2Events::USER_RESOLVE, 'checkUserPasswordValidity', )]
readonly class Oauth2UserResolveSubscriber
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private UserRepository $repository,
    ) {
    }

    public function checkUserPasswordValidity(UserResolveEvent $event): UserResolveEvent
    {
        $user = $this->repository->loadUserByIdentifier($event->getUsername());
        $isUserPasswordInvalid = $user instanceof LegacyPasswordAuthenticatedUserInterface && !$this->hasher->isPasswordValid($user, $event->getPassword());
        $event->setUser($isUserPasswordInvalid ? null : $user);

        return $event;
    }
}
