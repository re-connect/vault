<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Entity\Dossier;
use App\FormV2\RenameDocumentType;
use App\FormV2\SearchType;
use App\ManagerV2\DocumentManager;
use App\Repository\DossierRepository;
use App\ServiceV2\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentController extends AbstractController
{
    #[Route(path: '/beneficiary/{id}/documents', name: 'document_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function list(
        Request $request,
        Beneficiaire $beneficiary,
        DocumentManager $documentManager,
        PaginatorService $paginator,
    ): Response {
        $searchForm = $this->createForm(SearchType::class, null, [
            'attr' => ['data-controller' => 'ajax-list-filter'],
            'action' => $this->generateUrl('document_search', ['id' => $beneficiary->getId()]),
        ]);

        return $this->renderForm('v2/vault/document/index.html.twig', [
            'beneficiary' => $beneficiary,
            'foldersAndDocuments' => $paginator->create(
                $this->isLoggedInUser($beneficiary->getUser())
                    ? $documentManager->getAllFoldersAndDocumentsWithUrl($beneficiary)
                    : $documentManager->getSharedFoldersAndDocumentsWithUrl($beneficiary),
                $request->query->getInt('page', 1),
            ),
            'form' => $searchForm,
        ]);
    }

    #[Route(
        path: 'beneficiary/{id}/documents/upload',
        name: 'document_upload',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function upload(
        Request $request,
        Beneficiaire $beneficiary,
        DocumentManager $manager,
        DossierRepository $folderRepository
    ): Response {
        if (!$files = $request->files->get('files')) {
            return new Response(null, 400);
        }
        $folderId = $request->query->get('folder');

        $manager->uploadFiles(
            $files,
            $beneficiary,
            $folderId ? $folderRepository->find($folderId) : null
        );

        return new Response(null, 201);
    }

    #[Route(
        path: '/beneficiary/{id}/documents/search',
        name: 'document_search',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
        condition: 'request.isXmlHttpRequest()',
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function search(
        Request $request,
        Beneficiaire $beneficiary,
        DocumentManager $documentManager,
        PaginatorService $paginator,
    ): JsonResponse {
        $searchForm = $this->createForm(SearchType::class, null, [
            'attr' => ['data-controller' => 'ajax-list-filter'],
            'action' => $this->generateUrl('document_search', ['id' => $beneficiary->getId()]),
        ])->handleRequest($request);

        $search = $searchForm->get('search')->getData();

        return new JsonResponse([
            'html' => $this->renderForm('v2/vault/document/_list.html.twig', [
                'foldersAndDocuments' => $paginator->create(
                    $this->isLoggedInUser($beneficiary->getUser())
                        ? $documentManager->searchFoldersAndDocumentsWithUrl($beneficiary, $search)
                        : [],
                    $request->query->getInt('page', 1),
                ),
                'beneficiary' => $beneficiary,
                'form' => $searchForm,
            ])->getContent(),
        ]);
    }

    #[Route(path: '/document/{id}/detail', name: 'document_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'document')]
    public function detail(Document $document, DocumentManager $manager): Response
    {
        return $this->render('v2/vault/document/detail.html.twig', [
            'document' => $manager->getDocumentWithUrl($document),
            'beneficiary' => $document->getBeneficiaire(),
        ]);
    }

    #[Route(path: '/document/{id}/delete', name: 'document_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'document')]
    public function delete(Document $document, DocumentManager $manager): Response
    {
        $folder = $document->getDossier();
        $manager->delete($document);

        return $this->getDocumentPageRedirection($document, $folder);
    }

    #[Route(
        path: '/document/{id}/rename',
        name: 'document_rename',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('UPDATE', 'document')]
    public function rename(
        Request $request,
        Document $document,
        DocumentManager $manager,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(RenameDocumentType::class, $document, [
            'action' => $this->generateUrl('document_rename', ['id' => $document->getId()]),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $folderId = $document->getDossier()?->getId();

            return $this->redirect($folderId
                ? $this->generateUrl('folder', ['id' => $folderId])
                : $this->generateUrl('document_list', ['id' => $document->getBeneficiaireId()])
            );
        }

        return $this->renderForm('v2/vault/document/rename.html.twig', [
            'form' => $form,
            'document' => $manager->getDocumentWithUrl($document),
            'beneficiary' => $document->getBeneficiaire(),
        ]);
    }

    #[Route(path: 'document/{id}/download', name: 'document_download', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'document')]
    public function download(Document $document, DocumentManager $manager): Response
    {
        if (!$response = $manager->downloadDocument($document)) {
            $this->addFlash('danger', 'error_during_download');

            return $this->redirectToRoute('document_list', ['id' => $document->getBeneficiaireId()]);
        }

        return $response;
    }

    #[Route(
        path: 'document/{id}/toggle-visibility',
        name: 'document_toggle_visibility',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'PATCH'],
    )]
    #[IsGranted('TOGGLE_VISIBILITY', 'document')]
    public function toggleVisibility(Request $request, Document $document, DocumentManager $manager): Response
    {
        $manager->toggleVisibility($document);

        return $request->isXmlHttpRequest()
            ? new JsonResponse($document)
            : $this->redirectToRoute('document_list', ['id' => $document->getBeneficiaireId()]);
    }

    #[Route(
        path: '/documents/{id}/move/folder/{folderId?}',
        name: 'document_move_to_folder',
        requirements: ['id' => '\d+', 'folderId' => '\d+'],
        options: ['expose' => true],
        methods: ['GET'],
    )]
    #[ParamConverter('folder', class: 'App\Entity\Dossier', options: ['id' => 'folderId'])]
    #[IsGranted('UPDATE', 'document')]
    public function moveToFolder(
        Request $request,
        Document $document,
        DocumentManager $manager,
        ?Dossier $folder,
    ): Response {
        if ($folder) {
            $this->denyAccessUnlessGranted('UPDATE', $folder);
        }
        $initialParentFolder = $document->getDossier();
        $manager->move($document, $folder);
        $destinationFolder = $request->query->get('tree-view') ? $document->getDossier() : $initialParentFolder;

        return $this->getDocumentPageRedirection($document, $destinationFolder);
    }

    #[Route(
        path: 'document/{id}/tree-view-move',
        name: 'document_tree_view_move',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted('UPDATE', 'document')]
    public function treeViewMove(Document $document): Response
    {
        $beneficiary = $document->getBeneficiaire();

        return $this->render('v2/vault/folder/tree_view.html.twig', [
            'folders' => $this->isLoggedInUser($beneficiary->getUser())
                ? $beneficiary->getRootFolders()
                : $beneficiary->getSharedRootFolders(),
            'element' => $document,
            'beneficiary' => $document->getBeneficiaire(),
        ]);
    }

    private function getDocumentPageRedirection(Document $document, ?Dossier $folder): Response
    {
        return $folder
            ? $this->redirectToRoute('folder', ['id' => $folder->getId()])
            : $this->redirectToRoute('document_list', ['id' => $document->getBeneficiaireId()]);
    }
}
