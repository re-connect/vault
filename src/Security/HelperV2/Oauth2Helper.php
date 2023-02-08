<?php

namespace App\Security\HelperV2;

use App\Entity\Client;
use App\Entity\Interface\ClientResourceInterface;
use App\Repository\ClientRepository;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;

class Oauth2Helper
{
    public function __construct(
        private readonly ClientRepository $clientRepository
    ) {
    }

    public function getClient(OAuth2Token $token): ?Client
    {
        return $this->clientRepository->findForNewClientIdentifier($token->getOAuthClientId());
    }

    public function canClientAccessRessource(OAuth2Token $token, ClientResourceInterface $resource): bool
    {
        return $resource->hasExternalLinkForClient($this->getClient($token));
    }
}
