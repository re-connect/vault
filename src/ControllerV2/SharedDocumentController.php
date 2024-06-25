<?php

namespace App\ControllerV2;

use App\Entity\Contact;
use App\Entity\Document;
use App\ManagerV2\ContactManager;
use App\ManagerV2\DocumentManager;
use App\ManagerV2\SharedDocumentManager;
use App\ServiceV2\PaginatorService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SharedDocumentController extends AbstractController
{
    private const int CONTACT_LIST_LIMIT = 4;

    #[Route(path: 'document/{id<\d+>}/share', name: 'document_share', methods: ['GET', 'POST'])]
    #[IsGranted('UPDATE', 'document')]
    public function share(
        Request $request,
        Document $document,
        SharedDocumentManager $sharedDocumentManager,
        ContactManager $contactManager,
        PaginatorService $paginatorService,
    ): Response {
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
            'contacts' => $paginatorService->create(
                $contactManager->getContacts($document->getBeneficiaire()),
                $request->query->getInt('page', 1),
                self::CONTACT_LIST_LIMIT,
            ),
        ]);
    }

    #[Route(path: 'document/{id<\d+>}/share-with-contact/{contactId<\d+>}', name: 'document_share_with_contact', methods: ['GET'])]
    #[IsGranted('UPDATE', 'document')]
    #[IsGranted('UPDATE', 'contact')]
    public function shareWithContact(
        Request $request,
        Document $document,
        #[MapEntity(id: 'contactId')] Contact $contact,
        SharedDocumentManager $sharedDocumentManager,
    ): Response {
        if (!$contact->getEmail()) {
            $this->addFlash('error', 'contact_has_no_email');

            return $this->redirectToRoute('document_share', ['id' => $document->getId()]);
        }

        $sharedDocumentManager->generateSharedDocumentAndSendEmail(
            $document,
            $contact->getEmail(),
            $request->getLocale(),
        );

        return $this->redirectToRoute('list_documents', ['id' => $document->getBeneficiaire()?->getId()]);
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
