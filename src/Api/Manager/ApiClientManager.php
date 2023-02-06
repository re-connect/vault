<?php

namespace App\Api\Manager;

use App\Entity\Client;
use App\Repository\ClientRepository as OldClientRepository;
use League\Bundle\OAuth2ServerBundle\Repository\ClientRepository;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ApiClientManager
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ClientRepository $clientRepository,
        private readonly OldClientRepository $oldClientRepository,
    ) {
    }

    public function getCurrentClient(): ?ClientEntityInterface
    {
        $clientId = $this->getCurrentTokenClientId();

        return $clientId ? $this->clientRepository->getClientEntity($clientId) : null;
    }

    public function getCurrentOldClient(): ?Client
    {
        return $this->oldClientRepository->findForNewClient($this->getCurrentClient());
    }

    public function getCurrentTokenClientId(): ?string
    {
        $token = $this->tokenStorage->getToken();

        return $token instanceof OAuth2Token ? $token->getOAuthClientId() : null;
    }
}
