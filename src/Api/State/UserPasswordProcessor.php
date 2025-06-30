<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\ManagerV2\UserManager;

/** @implements ProcessorInterface<User, User|void> */
readonly class UserPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private UserManager $userManager,
    ) {
    }

    /** @param User $data */
    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data->getPlainPassword()) {
            $this->userManager->updatePasswordWithPlain($data);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
