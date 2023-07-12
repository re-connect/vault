<?php

namespace App\Security\HelperV2;

use App\Entity\Client;
use App\Entity\Interface\ClientResourceInterface;
use App\Repository\ClientRepository;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Oauth2Helper
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function getClient(OAuth2Token $token = null): ?Client
    {
        if (!$token) {
            /** @var OAuth2Token $token * */
            $token = $this->tokenStorage->getToken();
        }

        return $this->clientRepository->findForNewClientIdentifier($token->getOAuthClientId());
    }

    public function isClientGrantedAllPrivileges(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_OAUTH2_ALL');
    }

    public function canClientAccessRessource(OAuth2Token $token, ClientResourceInterface $resource): bool
    {
        return $this->isClientGrantedAllPrivileges() || $resource->hasExternalLinkForClient($this->getClient($token));
    }
}
