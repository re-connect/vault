<?php

namespace App\Security\Authorization\Voter;

use App\Api\Manager\ApiClientManager;
use App\Entity\TokenInterface;
use App\Provider\CentreProvider;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Entity\Client;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Repository\ClientRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class REVoter extends Voter
{
    public function __construct(
        protected readonly CentreProvider $provider,
        protected readonly TokenStorageInterface $tokenStorage,
        protected readonly ClientRepository $clientRepository,
        protected readonly ClientManagerInterface $clientManager,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ApiClientManager $apiClientManager,
    ) {
    }

    protected function accessClient($attribute, $token): bool
    {
        if (null !== $attribute && $token instanceof TokenInterface) {
            return !(null !== $this->apiClientManager->getCurrentOldClient());
        }

        return true;
    }

    public function getToken(): ?\Symfony\Component\Security\Core\Authentication\Token\TokenInterface
    {
        return $this->tokenStorage->getToken();
    }

    public function isClientAllowed(): bool
    {
        $clientEntity = $this->apiClientManager->getCurrentClient();
        if (!$clientEntity instanceof Client) {
            return false;
        }

        $clientId = $clientEntity->getIdentifier();
        $client = $this->clientManager->find($clientId);

        return $this->clientRepository->validateClient($clientId, $client->getSecret(), 'client_credentials');
    }
}
