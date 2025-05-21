<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Manager\ApiClientManager;
use App\Entity\Attributes\DonneePersonnelle;
use App\Entity\Attributes\User;
use App\Repository\BeneficiaireRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<DonneePersonnelle, DonneePersonnelle|void> */
readonly class PersonalDataStateProcessor implements ProcessorInterface
{
    public function __construct(private Security $security, private ProcessorInterface $persistProcessor, private BeneficiaireRepository $beneficiaireRepository, private ApiClientManager $apiClientManager)
    {
    }

    /** @param DonneePersonnelle $data */
    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?DonneePersonnelle
    {
        $user = $this->security->getUser();
        if ($user instanceof User && $user->isBeneficiaire() && $user->getSubjectBeneficiaire()) {
            $data->setBeneficiaire($user->getSubjectBeneficiaire());

            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        if ($data->beneficiaireId) {
            $beneficiary = $this->beneficiaireRepository->find($data->beneficiaireId);
            if (!$beneficiary || !$beneficiary->hasExternalLinkForClient($this->apiClientManager->getCurrentOldClient())) {
                throw new NotFoundHttpException('Beneficiaire Not Found');
            }
            $data->setBeneficiaire($beneficiary);

            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        throw new BadRequestHttpException('BeneficiaireId missing');
    }
}
