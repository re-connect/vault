<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\ApiOperations;
use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\DonneePersonnelle;
use App\Entity\Dossier;
use App\Entity\User;
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
        if (ApiOperations::isSameOperation($operation, ApiOperations::TOGGLE_VISIBILITY)) {
            $data->setBPrive(!$data->getBPrive());

            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $beneficiary = $this->getBeneficiary($data);
        $data->setBeneficiaire($beneficiary);

        if ($data instanceof Dossier && $data->dossierParentId) {
            $dossierParent = $this->dossierRepository->find($data->dossierParentId);
            if (!$dossierParent || $dossierParent->getBeneficiaire() !== $beneficiary) {
                throw new BadRequestHttpException('Dossier parent not found');
            }
            $data->setDossierParent($dossierParent);
            if ($dossierParent->getBPrive()) {
                $data->setBPrive(true);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function getBeneficiary(DonneePersonnelle $data): Beneficiaire
    {
        $user = $this->security->getUser();
        $isAuthenticatedAsClient = $this->oauth2Helper->isAuthenticatedAsClient();

        // Beneficiaries always create personal data for themselves
        if (!$isAuthenticatedAsClient && $user instanceof User && $user->isBeneficiaire() && $user->getSubjectBeneficiaire()) {
            return $user->getSubjectBeneficiaire();
        }

        $beneficiary = $data->beneficiaireId ? $this->beneficiaireRepository->find($data->beneficiaireId) : $data->getBeneficiaire();
        if (!$data->beneficiaireId && !$beneficiary) {
            throw new BadRequestHttpException('BeneficiaireId missing');
        }

        $canCreate = $isAuthenticatedAsClient
            ? $beneficiary?->hasExternalLinkForClient($this->apiClientManager->getCurrentOldClient())
            : $beneficiary && $this->security->isGranted('UPDATE', $beneficiary);
        if (!$beneficiary || !$canCreate) {
            throw new NotFoundHttpException('Beneficiaire Not Found');
        }

        return $beneficiary;
    }
}
