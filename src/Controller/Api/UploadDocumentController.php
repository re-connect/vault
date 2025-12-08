<?php

namespace App\Controller\Api;

use App\Api\Manager\ApiClientManager;
use App\ControllerV2\AbstractController;
use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\Document;
use App\Entity\Dossier;
use App\ManagerV2\DocumentManager;
use App\Repository\BeneficiaireRepository;
use App\Repository\DossierRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsController]
final class UploadDocumentController extends AbstractController
{
    public function __construct(private readonly ApiClientManager $apiClientManager, private readonly BeneficiaireRepository $beneficiaireRepository, private readonly DossierRepository $dossierRepository, private readonly Security $security)
    {
    }

    public function __invoke(Request $request, DocumentManager $documentManager): ?Document
    {
        $file = $this->getFile($request);
        $client = $this->getClient();
        $beneficiary = $this->getBeneficiary($request, $client);
        $folder = $this->getFolder($request, $beneficiary);

        return $documentManager->uploadFile($file, $beneficiary, $folder);
    }

    private function getBeneficiary(Request $request, Client $client): Beneficiaire
    {
        $user = $this->security->getUser();
        //         Prepare for password grant
        //        if ($user instanceof User && $user->isBeneficiaire() && $user->getSubjectBeneficiaire()) {
        //            return $user->getSubjectBeneficiaire();
        //        }

        $distantId = $request->request->get('distant_id');
        $beneficiaryId = $request->request->get('beneficiary_id');
        if (!$distantId && !$beneficiaryId) {
            throw new BadRequestHttpException('"distant_id" or "beneficiary_id" is required');
        }

        $beneficiary = $distantId ? $this->beneficiaireRepository->findByDistantId($distantId, $client->getRandomId()) : $this->beneficiaireRepository->find($beneficiaryId);
        if (!$beneficiary) {
            throw new NotFoundHttpException('Beneficiary not found');
        }
        //        Prepare for password grant
        //        if ($user instanceof User) {
        //            if (!$this->security->isGranted('UPDATE', $beneficiary)) {
        //                throw new AccessDeniedException('You do not have permission to access this beneficiary');
        //            }
        //        }

        if (!$beneficiary->hasExternalLinkForClient($client)) {
            throw new AccessDeniedException('You do not have permission to access this beneficiary');
        }

        return $beneficiary;
    }

    private function getFolder(Request $request, Beneficiaire $beneficiary): ?Dossier
    {
        $folder = null;
        $folderId = $request->request->get('folder_id');
        if ($folderId) {
            $folder = $this->dossierRepository->find($folderId);
            if (!$folder) {
                throw new BadRequestHttpException(sprintf('Folder not found for id %s', $folderId));
            }

            if ($folder->getBeneficiaire() !== $beneficiary) {
                throw new BadRequestHttpException('The folder does not belong to the beneficiary');
            }
        }

        return $folder;
    }

    private function getClient(): Client
    {
        if (!$this->apiClientManager->getCurrentTokenClientId()) {
            throw new BadRequestHttpException('Missing client_id in the request');
        }

        $client = $this->apiClientManager->getCurrentOldClient();
        if (!$client) {
            throw new BadRequestHttpException('Client not found for given client_id');
        }

        return $client;
    }

    private function getFile(Request $request)
    {
        $file = $request->files->get('file');
        if (!$file) {
            throw new BadRequestHttpException('"file" is required');
        }

        return $file;
    }
}
