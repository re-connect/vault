<?php

namespace App\ControllerV2;

use App\Entity\Document;
use App\ManagerV2\DocumentManager;
use App\ManagerV2\SharedDocumentManager;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SharedDocumentController extends AbstractController
{
    #[Route(path: 'document/{id}/share', name: 'document_share', methods: ['GET', 'POST'])]
    #[IsGranted('UPDATE', 'document')]
    public function share(Request $request, Document $document, SharedDocumentManager $sharedDocumentManager): Response
    {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'share_document_with'])
            ->setAction($this->generateUrl('document_share', ['id' => $document->getId()]))
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sharedDocumentManager->generateSharedDocumentAndSendEmail(
                $document,
                $form->get('email')->getData(),
                $request->getLocale(),
            );

            return $this->redirectToRoute('list_documents', ['id' => $document->getBeneficiaire()?->getId()]);
        }

        return $this->render('v2/vault/document/share.html.twig', [
            'form' => $form,
            'document' => $document,
            'beneficiary' => $document->getBeneficiaire(),
        ]);
    }

    #[Route(path: '/public/download-shared-document/{token}', name: 'download_shared_document', methods: ['GET'])]
    public function downloadSharedDocument(
        string $token,
        DocumentManager $documentManager,
        SharedDocumentManager $sharedDocumentManager,
    ): Response {
        $sharedDocument = $sharedDocumentManager->validateTokenAndFetchDocument($token);
        $document = $sharedDocument?->getDocument();

        if (!$sharedDocument || !$document) {
            return $this->redirectToRoute('home');
        }

        $documentManager->hydrateDocumentWithPresignedUrl($document);

        return $this->render('v2/vault/document/download.html.twig', [
            'sharedDocument' => $sharedDocument,
            'downloadLink' => $document->getPresignedUrl(),
        ]);
    }
}
