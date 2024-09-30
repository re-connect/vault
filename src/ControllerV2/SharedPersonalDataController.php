<?php

namespace App\ControllerV2;

use ApiPlatform\Api\UrlGeneratorInterface;
use App\Entity\Attributes\SharedFolder;
use App\Entity\Contact;
use App\Entity\Document;
use App\Entity\DonneePersonnelle;
use App\Entity\Dossier;
use App\Entity\Interface\ShareablePersonalData;
use App\Exception\SharedPersonalData\SharedPersonalDataException;
use App\ManagerV2\ContactManager;
use App\ManagerV2\DocumentManager;
use App\ManagerV2\FolderManager;
use App\ManagerV2\SharedPersonalDataManager;
use App\ServiceV2\PaginatorService;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SharedPersonalDataController extends AbstractController
{
    public function __construct(private readonly SharedPersonalDataManager $sharedPersonalDataManager)
    {
    }

    private const int CONTACT_LIST_LIMIT = 4;

    #[Route(path: 'document/{id<\d+>}/share', name: 'document_share', methods: ['GET', 'POST'])]
    #[IsGranted('UPDATE', 'document')]
    public function share(
        Request $request,
        Document $document,
        ContactManager $contactManager,
        PaginatorService $paginatorService,
    ): Response {
        // We need this check because we show beneficiary's contacts on this route
        if (!$this->isGranted('UPDATE', $document->getBeneficiaire())) {
            return $this->redirectToRoute('redirect_user');
        }

        $form = $this->generateShareForm(
            $this->generateUrl('document_share', ['id' => $document->getId()]),
        )->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->sharePersonalData(
                $document,
                $form->get('email')->getData(),
                $request->getLocale(),
            );

            return $this->redirectToRoute('list_documents', ['id' => $document->getBeneficiaire()?->getId()]);
        }

        return $this->render('v2/vault/personal_data/share_document.html.twig', [
            'form' => $form,
            'personalData' => $document,
            'beneficiary' => $document->getBeneficiaire(),
            'contacts' => $paginatorService->create(
                $contactManager->getContacts($document->getBeneficiaire()),
                $request->query->getInt('page', 1),
                self::CONTACT_LIST_LIMIT,
            ),
        ]);
    }

    #[Route(path: 'folder/{id<\d+>}/share', name: 'folder_share', methods: ['GET', 'POST'])]
    #[IsGranted('UPDATE', 'folder')]
    public function shareFolder(
        Request $request,
        Dossier $folder,
        ContactManager $contactManager,
        PaginatorService $paginatorService,
    ): Response {
        // We need this check because we show beneficiary's contacts on this route
        if (!$this->isGranted('UPDATE', $folder->getBeneficiaire())) {
            return $this->redirectToRoute('redirect_user');
        }

        $form = $this->generateShareForm(
            $this->generateUrl('folder_share', ['id' => $folder->getId()]),
        )->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->sharePersonalData(
                $folder,
                $form->get('email')->getData(),
                $request->getLocale(),
            );

            return $this->redirectToRoute('list_documents', ['id' => $folder->getBeneficiaire()?->getId()]);
        }

        return $this->render('v2/vault/personal_data/share_folder.html.twig', [
            'form' => $form,
            'personalData' => $folder,
            'beneficiary' => $folder->getBeneficiaire(),
            'contacts' => $paginatorService->create(
                $contactManager->getContacts($folder->getBeneficiaire()),
                $request->query->getInt('page', 1),
                self::CONTACT_LIST_LIMIT,
            ),
        ]);
    }

    private function generateShareForm(string $action): FormInterface
    {
        return $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'share_document_with'])
            ->setAction($action)
            ->getForm();
    }

    public function sharePersonalData(DonneePersonnelle $personalData, string $email, string $locale): void
    {
        try {
            $sharedPersonalData = $this->sharedPersonalDataManager->generateSharedPersonalData($personalData, $email, $locale);
            $this->sharedPersonalDataManager->sendSharedPersonalDataEmail($sharedPersonalData, $email);

            $this->addFlash('success', 'share_document_success');
        } catch (SharedPersonalDataException $e) {
            $this->addFlash('danger', $e->getMessage());
        }
    }

    /**
     * @param PaginationInterface<int, object> $contactList
     */
    public function renderShareView(FormInterface $form, DonneePersonnelle $personalData, PaginationInterface $contactList): Response
    {
        return $this->render('v2/vault/personal_data/share.html.twig', [
            'form' => $form,
            'personalData' => $personalData,
            'beneficiary' => $personalData->getBeneficiaire(),
            'contacts' => $contactList,
        ]);
    }

    #[Route(path: 'document/{id<\d+>}/share-with-contact/{contactId<\d+>}', name: 'document_share_with_contact', methods: ['GET'])]
    #[IsGranted('UPDATE', 'document')]
    #[IsGranted('UPDATE', 'contact')]
    public function shareDocumentWithContact(
        Request $request,
        Document $document,
        #[MapEntity(id: 'contactId')] Contact $contact,
    ): Response {
        if (!$contact->getEmail()) {
            $this->addFlash('error', 'contact_has_no_email');

            return $this->redirectToRoute('document_share', ['id' => $document->getId()]);
        }

        $this->sharePersonalData($document, $contact->getEmail(), $request->getLocale());

        return $this->redirectToRoute('list_documents', ['id' => $document->getBeneficiaire()?->getId()]);
    }

    #[Route(path: 'folder/{id<\d+>}/share-with-contact/{contactId<\d+>}', name: 'folder_share_with_contact', methods: ['GET'])]
    #[IsGranted('UPDATE', 'folder')]
    #[IsGranted('UPDATE', 'contact')]
    public function shareFolderWithContact(
        Request $request,
        Dossier $folder,
        #[MapEntity(id: 'contactId')] Contact $contact,
    ): Response {
        if (!$contact->getEmail()) {
            $this->addFlash('error', 'contact_has_no_email');

            return $this->redirectToRoute('folder_share', ['id' => $folder->getId()]);
        }

        $this->sharePersonalData($folder, $contact->getEmail(), $request->getLocale());

        return $this->redirectToRoute('list_documents', ['id' => $folder->getBeneficiaire()?->getId()]);
    }

    #[Route(path: '/public/download-shared-personal-data/{token}', name: 'download_shared_personal_data', methods: ['GET'])]
    public function downloadSharedPersonalData(
        string $token,
        DocumentManager $documentManager,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        try {
            $sharedPersonalData = $this->sharedPersonalDataManager->fetchPersonalData($token);
        } catch (SharedPersonalDataException $e) {
            $sharedPersonalData = null;
            $this->addFlash('danger', $e->getMessage());
        }

        $personalData = $sharedPersonalData?->getPersonalData();

        if (!$sharedPersonalData || !$personalData instanceof ShareablePersonalData) {
            return $this->redirectToRoute('home');
        }

        $downloadLink = null;
        if ($personalData instanceof Document) {
            $documentManager->hydrateDocumentWithPresignedUrl($personalData);
            $downloadLink = $personalData->getPresignedUrl();
        } elseif ($personalData instanceof Dossier) {
            $downloadLink = $urlGenerator->generate('download_shared_folder', ['token' => $token]);
        }

        return $this->render('v2/vault/personal_data/download.html.twig', [
            'sharedPersonalData' => $sharedPersonalData,
            'downloadLink' => $downloadLink,
        ]);
    }

    #[Route(path: '/public/download-shared-folder/{token}', name: 'download_shared_folder', methods: ['GET'])]
    public function downloadSharedFolder(
        string $token,
        FolderManager $folderManager,
    ): Response {
        try {
            $sharedFolder = $this->sharedPersonalDataManager->fetchPersonalData($token);
        } catch (SharedPersonalDataException $e) {
            $sharedFolder = null;
            $this->addFlash('danger', $e->getMessage());
        }

        if (!$sharedFolder instanceof SharedFolder) {
            return $this->redirectToRoute('home');
        }

        $streamedResponse = $folderManager->getZipFromFolder($sharedFolder->getFolder(), $sharedFolder->isSharedByOwner());

        if (!$streamedResponse) {
            $this->addFlash('error', 'error_during_download');

            return $this->redirectToRoute('download_shared_personal_data', ['token' => $token]);
        }

        return $streamedResponse;
    }
}
