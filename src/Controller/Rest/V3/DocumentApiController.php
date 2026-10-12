<?php

namespace App\Controller\Rest\V3;

use App\ControllerV2\AbstractController;
use App\Entity\Document;
use App\Factory\SharedDocumentFactory;
use App\ManagerV2\FolderableItemManager;
use App\Repository\DossierRepository;
use App\ServiceV2\Mailer\MailerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/v3/documents', format: 'json')]
#[IsGranted('ROLE_USER')]
final class DocumentApiController extends AbstractController
{
    #[Route(path: '/{id<\d+>}/share', requirements: ['id' => '\d{1,10}'], methods: ['POST'])]
    #[IsGranted('UPDATE', 'document')]
    public function shareDocument(Request $request, Document $document, SharedDocumentFactory $factory, MailerService $mailerService): JsonResponse
    {
        $email = $request->request->get('email');
        if (!$email) {
            return $this->json(['error' => 'You must provide an email'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $sharedDocument = $factory->generateSharedDocument($user, $document, $email, $user->getLastLang());
        $mailerService->sendSharedDocumentLink($sharedDocument, $email);

        return $this->json($sharedDocument, Response::HTTP_OK);
    }

    /**
     * Moves a document into a folder, or back to the root folder when "folder_id" is null.
     */
    #[Route(path: '/{id<\d+>}/move', methods: ['PATCH'])]
    #[IsGranted('UPDATE', 'document')]
    public function move(Request $request, Document $document, DossierRepository $folderRepository, FolderableItemManager $folderableItemManager): JsonResponse
    {
        $folderId = $request->request->get('folder_id');
        $folder = null;
        if (null !== $folderId) {
            $folder = $folderRepository->find($folderId);
            if (!$folder || $folder->getBeneficiaire() !== $document->getBeneficiaire()) {
                throw new BadRequestHttpException(sprintf('Folder not found for id %s', $folderId));
            }
            $this->denyAccessUnlessGranted('UPDATE', $folder);
        }

        try {
            $folderableItemManager->move($document, $folder);
        } catch (\Exception $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        return $this->json($document, Response::HTTP_OK, [], ['groups' => ['v3:document:read']]);
    }
}
