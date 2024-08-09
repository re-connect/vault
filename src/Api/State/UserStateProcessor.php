<?php

namespace App\Api\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\UserDto;
use App\ManagerV2\UserManager;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class UserStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $persistProcessor,
        private UserManager $userManager,
        private UserRepository $repository,
    ) {
    }

    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof UserDto || !array_key_exists('id', $uriVariables)) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $data = $data->patchUser($this->repository->find($uriVariables['id']));

        if (
            $data->getPlainPassword()
            && $data->getCurrentPassword()
            && $this->userManager->isPasswordValid($data, $data->getCurrentPassword())
        ) {
            $this->userManager->updatePasswordWithPlain($data);
        }
        $data->eraseCredentials();

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
