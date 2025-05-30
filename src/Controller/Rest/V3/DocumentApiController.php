<?php

namespace App\Controller\Rest\V3;

use App\ControllerV2\AbstractController;
use App\Entity\Attributes\Document;
use App\Factory\SharedDocumentFactory;
use App\ServiceV2\Mailer\MailerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
}
