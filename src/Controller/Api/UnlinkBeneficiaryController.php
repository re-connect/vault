<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Beneficiaire;
use App\ServiceV2\BeneficiaryClientLinkService;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class UnlinkBeneficiaryController extends AbstractController
{
    public function __invoke(?Beneficiaire $beneficiary, BeneficiaryClientLinkService $service): ?Beneficiaire
    {
        if (!$beneficiary) {
            throw new BadRequestHttpException('"beneficiary" not found');
        }

        return $service->unlinkBeneficiaryForCurrentClient($beneficiary);
    }
}
