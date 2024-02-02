<?php

namespace App\EventSubscriber\Api;

use App\Event\UserEvent;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsEventListener(RequestEvent::ACCESS_TOKEN_ISSUED, 'onAccessTokenIssued')]
readonly class Oauth2TokenIssuedSubscriber
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private UserRepository $repository,
        private EntityManagerInterface $em,
    ) {
    }

    public function onAccessTokenIssued(RequestAccessTokenEvent $event): void
    {
        $userId = (string) $event->getAccessToken()->getUserIdentifier();
        if (!$userId) {
            return;
        }
        $user = $this->repository->loadUserByIdentifier($userId);
        if (!$user) {
            return;
        }
        if (!$user->hasLoginToday()) {
            $user->setDerniereConnexionAt(new \DateTime());
            $this->em->flush();
        }
        $this->dispatcher->dispatch(new UserEvent($user, !$user->hasLoginToday()));
    }
}
