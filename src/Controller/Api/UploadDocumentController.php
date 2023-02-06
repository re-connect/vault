<?php

namespace App\Controller\Api;

use App\Api\Manager\ApiClientManager;
use App\Controller\AbstractController;
use App\Entity\Document;
use App\ManagerV2\DocumentManager;
use App\Repository\BeneficiaireRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class UploadDocumentController extends AbstractController
{
    public function __invoke(Request $request, ApiClientManager $apiClientManager, BeneficiaireRepository $beneficiaireRepository, DocumentManager $documentManager): ?Document
    {
        if (!($file = $request->files->get('file'))) {
            throw new BadRequestHttpException('"file" is required');
        } elseif (!($distantId = $request->request->get('distant_id'))) {
            throw new BadRequestHttpException('"distant_id" is required');
        } elseif (!$apiClientManager->getCurrentTokenClientId()) {
            throw new BadRequestHttpException('Missing client_id in the request');
        } elseif (!$client = $apiClientManager->getCurrentOldClient()) {
            throw new BadRequestHttpException('Client not found for given client_id');
        } elseif (!($beneficiary = $beneficiaireRepository->findByDistantId($distantId, $client->getRandomId()))) {
            throw new BadRequestHttpException(sprintf('Beneficiary not found for external id %s', $distantId));
        }

        return $documentManager->uploadFile($file, $beneficiary);
    }
}
