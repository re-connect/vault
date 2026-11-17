<?php

namespace App\Controller\Api;

use App\ControllerV2\AbstractController;
use App\Repository\BeneficiaireRepository;
use App\ServiceV2\BeneficiaryClientLinkService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class UnlinkBeneficiaryController extends AbstractController
{
    public function __invoke(int $id, BeneficiaryClientLinkService $service, BeneficiaireRepository $beneficiaireRepository, EntityManagerInterface $em): JsonResponse
    {
        $em->clear();
        $beneficiary = $beneficiaireRepository->find($id);

        if (!$beneficiary) {
            throw new BadRequestHttpException('"beneficiary" not found');
        }
        // API Platform does not evaluate the operation "security" when the operation has its own controller
        $this->denyAccessUnlessGranted('UPDATE', $beneficiary);

        $service->unlinkBeneficiaryForCurrentClient($beneficiary);

        return $this->json($beneficiary, Response::HTTP_OK, [], ['groups' => ['v3:beneficiary:read', 'v3:user:read']]);
    }
}
