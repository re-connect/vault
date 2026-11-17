<?php

namespace App\Controller\Api;

use App\Api\Manager\ApiClientManager;
use App\ControllerV2\AbstractController;
use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\Dossier;
use App\Entity\User;
use App\ManagerV2\DocumentManager;
use App\Repository\BeneficiaireRepository;
use App\Repository\DossierRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
final class UploadDocumentController extends AbstractController
{
    public function __construct(private readonly ApiClientManager $apiClientManager, private readonly BeneficiaireRepository $beneficiaireRepository, private readonly DossierRepository $dossierRepository, private readonly Security $security)
    {
    }

    // API Platform does not evaluate the operation "security" when the operation has its own controller
    #[IsGranted(new Expression("is_granted('ROLE_OAUTH2_DOCUMENTS_CREATE') or is_granted('ROLE_USER')"))]
    public function __invoke(Request $request, DocumentManager $documentManager): Response
    {
        $file = $this->getFile($request);
        $user = $this->security->getUser();
        $beneficiary = $user instanceof User ? $this->getBeneficiaryForUser($request, $user) : $this->getBeneficiaryForClient($request, $this->getClient());
        $folder = $this->getFolder($request, $beneficiary);
        $document = $documentManager->uploadFile($file, $beneficiary, $folder);
        if (!$document) {
            throw new BadRequestHttpException(sprintf('The file "%s" could not be uploaded', $file->getClientOriginalName()));
        }

        return $this->json($document, Response::HTTP_CREATED, [], ['groups' => 'document:read']);
    }

    private function getBeneficiaryForUser(Request $request, User $user): Beneficiaire
    {
        // Beneficiaries always upload for themselves
        if ($user->isBeneficiaire() && $user->getSubjectBeneficiaire()) {
            return $user->getSubjectBeneficiaire();
        }

        $beneficiaryId = $request->request->get('beneficiary_id');
        if (!$beneficiaryId) {
            throw new BadRequestHttpException('"beneficiary_id" is required');
        }

        $beneficiary = $this->beneficiaireRepository->find($beneficiaryId);
        if (!$beneficiary) {
            throw new NotFoundHttpException('Beneficiary not found');
        }

        if (!$this->security->isGranted('UPDATE', $beneficiary)) {
            throw new AccessDeniedException('You do not have permission to access this beneficiary');
        }

        return $beneficiary;
    }

    private function getBeneficiaryForClient(Request $request, Client $client): Beneficiaire
    {
        $distantId = $request->request->get('distant_id');
        $beneficiaryId = $request->request->get('beneficiary_id');
        if (!$distantId && !$beneficiaryId) {
            throw new BadRequestHttpException('"distant_id" or "beneficiary_id" is required');
        }

        $beneficiary = $distantId ? $this->beneficiaireRepository->findByDistantId($distantId, $client->getRandomId()) : $this->beneficiaireRepository->find($beneficiaryId);
        if (!$beneficiary) {
            throw new NotFoundHttpException('Beneficiary not found');
        }

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

            if ($this->security->getUser() instanceof User && !$this->security->isGranted('UPDATE', $folder)) {
                throw new AccessDeniedException('You do not have permission to access this folder');
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
