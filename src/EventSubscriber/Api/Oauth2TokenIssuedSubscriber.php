<?php

namespace App\EventSubscriber\Api;

use App\Event\UserEvent;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Oauth2TokenIssuedSubscriber implements EventSubscriberInterface
{
    private EventDispatcherInterface $dispatcher;
    private UserRepository $repository;
    private EntityManagerInterface $em;

    public function __construct(EventDispatcherInterface $dispatcher, UserRepository $repository, EntityManagerInterface $em)
    {
        $this->dispatcher = $dispatcher;
        $this->repository = $repository;
        $this->em = $em;
    }

    public function onAccessTokenIssued(RequestAccessTokenEvent $event): void
    {
        $userId = (string) $event->getAccessToken()->getUserIdentifier();
        if ($userId) {
            $user = $this->repository->loadUserByIdentifier($userId);
            if ($user) {
                if (!$user->hasLoginToday()) {
                    $user->setDerniereConnexionAt(new \DateTime());
                    $this->em->flush();
                }
                $this->dispatcher->dispatch(new UserEvent($user, !$user->hasLoginToday()));
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::ACCESS_TOKEN_ISSUED => 'onAccessTokenIssued',
        ];
    }
}
