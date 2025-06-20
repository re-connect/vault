<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\ApiOperations;
use App\Api\Manager\ApiClientManager;
use App\Entity\DonneePersonnelle;
use App\Repository\BeneficiaireRepository;
use App\Repository\DossierRepository;
use App\Security\HelperV2\Oauth2Helper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<DonneePersonnelle, DonneePersonnelle|void> */
readonly class PersonalDataStateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private Oauth2Helper $oauth2Helper,
        private ProcessorInterface $persistProcessor,
        private BeneficiaireRepository $beneficiaireRepository,
        private ApiClientManager $apiClientManager,
        private DossierRepository $dossierRepository,
    ) {
    }

    /** @param DonneePersonnelle $data */
    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?DonneePersonnelle
    {
        $user = $this->security->getUser();
        if (!$this->oauth2Helper->isAuthenticatedAsClient() && $user->isBeneficiaire() && $user->getSubjectBeneficiaire()) {
            $data->setBeneficiaire($user->getSubjectBeneficiaire());

            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        if ($data->beneficiaireId) {
            $beneficiary = $this->beneficiaireRepository->find($data->beneficiaireId);
            if (!$beneficiary || !$beneficiary->hasExternalLinkForClient($this->apiClientManager->getCurrentOldClient())) {
                throw new NotFoundHttpException('Beneficiaire Not Found');
            }
            $data->setBeneficiaire($beneficiary);
            if ($data instanceof Dossier && $data->dossierParentId) {
                $dossierParent = $this->dossierRepository->find($data->dossierParentId);
                if (!$dossierParent) {
                    throw new BadRequestHttpException('Dossier parent not found');
                }
                $data->setDossierParent($dossierParent);
            }

            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        if ($data instanceof DonneePersonnelle && ApiOperations::isSameOperation($operation, ApiOperations::TOGGLE_VISIBILITY)) {
            $data->setBPrive(!$data->getBPrive());

            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        throw new BadRequestHttpException('BeneficiaireId missing');
    }
}
