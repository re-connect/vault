<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\DonneePersonnelle;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

/** @implements ProcessorInterface<DonneePersonnelle, DonneePersonnelle|void> */
readonly class PersonalDataStateProcessor implements ProcessorInterface
{
    public function __construct(private Security $security, private ProcessorInterface $persistProcessor)
    {
    }

    /** @param DonneePersonnelle $data */
    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DonneePersonnelle
    {
        $user = $this->security->getUser();
        if ($user instanceof User && $user->isBeneficiaire() && $user->getSubjectBeneficiaire()) {
            $data->setBeneficiaire($user->getSubjectBeneficiaire());
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
