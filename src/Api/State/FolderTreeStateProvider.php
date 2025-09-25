<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Attributes\Dossier;
use Doctrine\ORM\EntityManagerInterface;

class FolderTreeStateProvider implements ProviderInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $repo = $this->em->getRepository(Dossier::class);

        return $repo->findBy(['dossierParent' => null, 'beneficiaire' => $uriVariables['id'], 'bPrive' => false]);
    }
}
