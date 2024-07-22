<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Manager\ApiClientManager;
use App\Entity\DonneePersonnelle;
use App\Entity\User;
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
            $benef = $this->beneficiaireRepository->find($data->beneficiaireId);
            if ($benef && $benef->hasExternalLinkForClient($this->apiClientManager->getCurrentOldClient())) {
                $data->setBeneficiaire($benef);

                return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
            }

            throw new NotFoundHttpException('Beneficiaire Not Found');
        }

        throw new BadRequestHttpException('BeneficiaireId missing');
    }
}
