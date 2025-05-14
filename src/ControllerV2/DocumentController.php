<?php

namespace App\ControllerV2;

use App\Entity\Attributes\Document;
use App\Entity\Dossier;
use App\FormV2\RenameDocumentType;
use App\ManagerV2\DocumentManager;
use App\ManagerV2\FolderableItemManager;
use App\ManagerV2\FolderManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DocumentController extends AbstractController
{
    #[Route(path: '/document/{id}/detail', name: 'document_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'document')]
    public function detail(Document $document, DocumentManager $manager): Response
    {
        return $this->render('v2/vault/document/detail.html.twig', [
            'document' => $document,
            'beneficiary' => $document->getBeneficiaire(),
        ]);
    }

    #[Route(path: '/document/{id}/delete', name: 'document_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('DELETE', 'document')]
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
                : $this->generateUrl('list_documents', ['id' => $document->getBeneficiaireId()])
            );
        }

        return $this->render('v2/vault/document/rename.html.twig', [
            'form' => $form,
            'document' => $manager->hydrateDocumentAndThumbnailWithUrl($document),
            'beneficiary' => $document->getBeneficiaire(),
        ]);
    }

    #[Route(path: 'document/{id}/download', name: 'document_download', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'document')]
    public function download(Document $document, DocumentManager $manager): Response
    {
        if (!$response = $manager->downloadDocument($document)) {
            $this->addFlash('danger', 'error_during_download');

            return $this->redirectToRoute('list_documents', ['id' => $document->getBeneficiaireId()]);
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
            : $this->redirectToRoute('list_documents', ['id' => $document->getBeneficiaireId()]);
    }

    #[Route(
        path: '/documents/{id}/move/folder/{folderId?}',
        name: 'document_move_to_folder',
        requirements: ['id' => '\d+', 'folderId' => '\d+'],
        options: ['expose' => true],
        methods: ['GET'],
    )]
    #[IsGranted('UPDATE', 'document')]
    public function moveToFolder(
        Request $request,
        Document $document,
        FolderableItemManager $manager,
        #[MapEntity(id: 'folderId')] ?Dossier $folder,
    ): Response {
        if ($folder) {
            $this->denyAccessUnlessGranted('UPDATE', $folder);
        }
        $initialParentFolder = $document->getDossier();

        try {
            $manager->move($document, $folder);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

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
    public function treeViewMove(Document $document, FolderManager $folderManager): Response
    {
        $beneficiary = $document->getBeneficiaire();

        return $this->render('v2/vault/folder/tree_view.html.twig', [
            'folders' => $beneficiary ? $folderManager->getRootFolders($beneficiary) : [],
            'element' => $document,
            'beneficiary' => $document->getBeneficiaire(),
        ]);
    }

    private function getDocumentPageRedirection(Document $document, ?Dossier $folder): Response
    {
        return $folder
            ? $this->redirectToRoute('folder', ['id' => $folder->getId()])
            : $this->redirectToRoute('list_documents', ['id' => $document->getBeneficiaireId()]);
    }
}
