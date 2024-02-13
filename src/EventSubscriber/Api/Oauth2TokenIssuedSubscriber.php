<?php

namespace App\EventSubscriber\Api;

use App\Event\UserEvent;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsEventListener(RequestEvent::ACCESS_TOKEN_ISSUED, 'onAccessTokenIssued')]
readonly class Oauth2TokenIssuedSubscriber
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private UserRepository $repository,
        private EntityManagerInterface $em,
        private CodeGeneratorInterface $codeGenerator,
        private bool $appli2faEnabled,
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

        $isMfaEnabled = $user->isMfaEnabled() && $this->appli2faEnabled;

        if ($isMfaEnabled) {
            parse_str($event->getRequest()->getServerParams()['QUERY_STRING'], $queryParams);
            $mfaCodeSent = $queryParams['_auth_code'] ?? null;

            if (!$mfaCodeSent) {
                $this->codeGenerator->generateAndSend($user);
                $user->setMfaPending(true);
            } else {
                $user->setMfaValid($mfaCodeSent === $user->getEmailAuthCode());
            }
        }

        $this->em->flush();
    }
}
