<?php

namespace App\EventSubscriber\Api;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\Bundle\OAuth2ServerBundle\Event\TokenRequestResolveEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[AsEventListener(OAuth2Events::TOKEN_REQUEST_RESOLVE, 'checkOTPCode', 2)]
readonly class Oauth2ResponseSubscriber
{
    public function checkOTPCode(TokenRequestResolveEvent $event): TokenRequestResolveEvent
    {
        if ('' === $this->extractUserIdFromResponse($event->getResponse())) {
            $event->setResponse(new JsonResponse(['login' => 'success', 'two_factor_complete' => false]));
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
        return json_decode($response->getContent())->access_token;
    }

    public function extractUserIdFromJwt(string $jwt): string
    {
        return (string) Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('empty', 'empty')
        )
            ->parser()
            ->parse($jwt)
            ->claims()
            ->get('sub');
    }
}
