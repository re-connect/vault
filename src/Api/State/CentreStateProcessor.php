<?php

namespace App\Api\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\CentreDto;
use App\Api\Manager\ApiClientManager;
use App\Entity\Association;
use App\Entity\Centre;
use App\Entity\CreatorClient;
use App\Entity\User;
use App\ManagerV2\UserManager;
use App\Repository\AssociationRepository;
use App\Repository\RegionRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class CentreStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $persistProcessor,
        private ApiClientManager $apiClientManager,
        private AssociationRepository $associationRepository,
        private RegionRepository $regionRepository,
        private UserManager $userManager,
    ) {
    }

    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $client = $this->apiClientManager->getCurrentOldClient();

        if ($client && $operation instanceof Post && $data instanceof CentreDto) {
            $relay = $data
                ->toCentre()
                ->setRegion($this->regionRepository->findOneBy(['name' => $data->region]))
                ->addCreator(new CreatorClient($client));
            $this->addAssociation($data, $relay);

            return $this->persistProcessor->process($relay, $operation, $uriVariables, $context);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function addAssociation(mixed $data, Centre $relay): void
    {
        if ($data->association) {
            $relay
                ->setAssociation(
                    $this->associationRepository->findOneBy(['nom' => $data->association])
                    ?? $this->createAssociation($data->association)
                );
        }
    }

    private function createAssociation(string $name): Association
    {
        $association = (new Association())->setNom($name);
        $userAssociation = (new User())
            ->setPlainPassword($this->userManager->getRandomPassword())
            ->setNom($association->getNom())
            ->setTypeUser(User::USER_TYPE_ASSOCIATION)
            ->disable();

        $this->userManager->updatePasswordWithPlain($userAssociation);
        $userAssociation->setUsername($association->getNom());

        return $association->setUser($userAssociation);
    }
}
