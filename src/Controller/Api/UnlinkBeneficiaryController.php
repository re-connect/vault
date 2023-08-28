<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Beneficiaire;
use App\Repository\BeneficiaireRepository;
use App\ServiceV2\BeneficiaryClientLinkService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class UnlinkBeneficiaryController extends AbstractController
{
    public function __invoke(int $id, BeneficiaryClientLinkService $service, BeneficiaireRepository $beneficiaireRepository, EntityManagerInterface $em): Beneficiaire
    {
        $em->clear();
        $beneficiary = $beneficiaireRepository->find($id);

        if (!$beneficiary) {
            throw new BadRequestHttpException('"beneficiary" not found');
        }

        $service->unlinkBeneficiaryForCurrentClient($beneficiary);

        return $beneficiary;
    }
}
