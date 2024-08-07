<?php

namespace App\EventSubscriber\Api;

use App\Repository\UserRepository;
use App\ServiceV2\GdprService;
use Doctrine\ORM\EntityManagerInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use League\Bundle\OAuth2ServerBundle\Event\TokenRequestResolveEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[AsEventListener(OAuth2Events::TOKEN_REQUEST_RESOLVE, 'onTokenResolve', 2)]
readonly class Oauth2ResponseSubscriber
{
    public function __construct(
        private UserRepository $repository,
        private EntityManagerInterface $em,
        private GdprService $gdprService,
        private bool $appliExpirePassword,
    ) {
    }

    public function onTokenResolve(TokenRequestResolveEvent $event): TokenRequestResolveEvent
    {
        $username = $this->extractUserIdFromResponse($event->getResponse());
        if (!$username) {
            return $event;
        }

        $user = $this->repository->findByUsername($username);
        if (!$user) {
            return $event;
        }

        if (!$user->isEnabled()) {
            $event->setResponse(new JsonResponse(['login' => 'failure', 'disabled' => true], Response::HTTP_UNAUTHORIZED));
        } elseif ($user->isBeingCreated()) {
            $event->setResponse(new JsonResponse(['login' => 'failure', 'isBeingCreated' => true], Response::HTTP_UNAUTHORIZED));
        }

        if (!$user->hasPasswordWithLatestPolicy()) {
            $event->setResponse(new JsonResponse(['login' => 'success', 'weak_password' => true]));
        } elseif (false === $user->isBeneficiaire() && $this->gdprService->isPasswordExpired($user) && $this->appliExpirePassword) {
            $event->setResponse(new JsonResponse(['login' => 'success', 'expired_password' => true]));
        }

        if (!$user->isMfaEnabled()) {
            return $event;
        }

        if ($user->isMfaPending()) {
            $event->setResponse(new JsonResponse(['login' => 'success', 'two_factor_complete' => false]));
        } elseif (!$user->isMfaValid()) {
            $event->setResponse(new JsonResponse(['login' => 'failure', 'two_factor_complete' => false], Response::HTTP_UNAUTHORIZED));
        } else {
            $user->resetAuthCodes();
            $this->em->flush();
        }

        return $event;
    }

    public function extractUserIdFromResponse(Response $response): string
    {
        try {
            $jwt = $this->decodeAccessTokenFromResponse($response);

            return $this->extractUserIdFromJwt($jwt);
        } catch (\Exception) {
            return '';
        }
    }

    public function decodeAccessTokenFromResponse(Response $response): string
    {
        return json_decode($response->getContent() ?: '')->access_token;
    }

    public function extractUserIdFromJwt(string $jwt): string
    {
        if ('' === $jwt) {
            throw new \InvalidArgumentException('JWT can not be empty');
        }

        /** @var UnencryptedToken $token */
        $token = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('empty', 'empty')
        )
            ->parser()
            ->parse($jwt);

        return (string) $token
            ->claims()
            ->get('sub');
    }
}
