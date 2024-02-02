<?php

namespace App\EventSubscriber\Api;

use App\Repository\UserRepository;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\Security\User\NullUser;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;

#[AsEventListener(OAuth2Events::USER_RESOLVE, 'checkUserPasswordValidity', )]
readonly class Oauth2UserResolveSubscriber
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private UserRepository $repository,
        private CodeGeneratorInterface $codeGenerator,
        private RequestStack $requestStack,
    ) {
    }

    public function checkUserPasswordValidity(UserResolveEvent $event): UserResolveEvent
    {
        $user = $this->repository->loadUserByIdentifier($event->getUsername());
        $isUserPasswordInvalid = $user instanceof LegacyPasswordAuthenticatedUserInterface && !$this->hasher->isPasswordValid($user, $event->getPassword());
        $event->setUser($isUserPasswordInvalid ? null : $user);

        if (!$user->isMfaEnabled()) {
            return $event;
        }

        $currentRequest = $this->requestStack->getCurrentRequest();
        $sentAuthCode = $currentRequest->request->get('_auth_code');

        if (!$sentAuthCode) {
            $event->setUser(new NullUser());
            $this->codeGenerator->generateAndSend($user);
        } elseif ($sentAuthCode !== $user->getEmailAuthCode()) {
            $event->setUser(new NullUser());
        }

        return $event;
    }
}
