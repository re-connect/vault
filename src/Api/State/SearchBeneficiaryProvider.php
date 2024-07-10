<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\UserRepository;

readonly class SearchBeneficiaryProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    #[\Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$context['filters']) {
            return null;
        }

        $username = $context['filters']['username'] ?? null;
        if (!$username) {
            return null;
        }
        $user = $this->userRepository->findByUsername(trim((string) $username, '"'));

        return $user ? ['beneficiary' => $user->getSubjectBeneficiaire()] : null;
    }
}
