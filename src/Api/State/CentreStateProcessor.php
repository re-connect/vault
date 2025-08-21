<?php

namespace App\Api\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Manager\ApiClientManager;
use App\Entity\Attributes\CreatorClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class CentreStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $persistProcessor,
        private ApiClientManager $manager)
    {
    }

    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $client = $this->manager->getCurrentOldClient();

        if ($client && $operation instanceof Post) {
            $creator = new CreatorClient($client);
            $data->addCreator($creator);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
