<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Dossier;
use Doctrine\ORM\EntityManagerInterface;

class FolderTreeStateProvider implements ProviderInterface
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly ApiClientManager $apiClientManager)
    {
    }

    #[\Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $repo = $this->em->getRepository(Dossier::class);
        $benef = $this->em->getRepository(Beneficiaire::class)->find($uriVariables['id']);
        $client = $this->apiClientManager->getCurrentOldClient();
        if (!$benef || ($client && !$benef->hasExternalLinkForClient($client))) {
            return null;
        }

        return $repo->findBy(['dossierParent' => null, 'beneficiaire' => $benef, 'bPrive' => false]);
    }
}
