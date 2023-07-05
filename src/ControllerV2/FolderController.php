<?php

namespace App\ControllerV2;

use App\Entity\Dossier;
use App\FormV2\FolderType;
use App\FormV2\Search\SearchType;
use App\ManagerV2\FolderableItemManager;
use App\ManagerV2\FolderManager;
use App\ServiceV2\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FolderController extends AbstractController
{
    #[Route(path: '/folder/{id}', name: 'folder', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'folder')]
    public function list(
        Request $request,
        Dossier $folder,
        FolderableItemManager $manager,
        PaginatorService $paginator,
    ): Response {
        $beneficiary = $folder->getBeneficiaire();
        $searchForm = $this->createForm(SearchType::class, null, [
            'attr' => ['data-controller' => 'ajax-list-filter'],
            'action' => $this->generateUrl('search_folders', ['id' => $beneficiary->getId(), 'parentFolderId' => $folder->getId()]),
        ]);

        return $this->render('v2/vault/document/index.html.twig', [
            'beneficiary' => $beneficiary,
            'foldersAndDocuments' => $paginator->create(
                $manager->getFoldersAndDocumentsWithUrl($beneficiary, $folder),
                $request->query->getInt('page', 1),
            ),
            'currentFolder' => $folder,
            'form' => $searchForm,
        ]);
    }

    #[Route(path: '/folder/{id}/rename', name: 'folder_rename', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('UPDATE', 'folder')]
    public function rename(
        Request $request,
        Dossier $folder,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(FolderType::class, $folder, [
            'action' => $this->generateUrl('folder_rename', ['id' => $folder->getId()]),
            'rename_only' => true,
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $parentFolder = $folder->getDossierParent();

            return $this->getFolderPageRedirection($folder, $parentFolder);
        }

        return $this->render('v2/vault/folder/rename.html.twig', [
            'form' => $form,
            'folder' => $folder,
            'beneficiary' => $folder->getBeneficiaire(),
        ]);
    }

    #[Route(
        path: '/folder/{id}/move-to-folder/{folderId?}',
        name: 'folder_move_to_folder',
        requirements: ['id' => '\d+', 'folderId' => '\d+'],
        options: ['expose' => true],
        methods: ['GET'],
    )]
    #[ParamConverter('parentFolder', class: 'App\Entity\Dossier', options: ['id' => 'folderId'])]
    #[IsGranted('UPDATE', 'folder')]
    public function moveToFolder(
        Request $request,
        Dossier $folder,
        FolderableItemManager $manager,
        ?Dossier $parentFolder = null,
    ): Response {
        if ($parentFolder) {
            $this->denyAccessUnlessGranted('UPDATE', $parentFolder);
        }
        $initialParentFolder = $folder->getDossierParent();
        $manager->move($folder, $parentFolder);
        $destinationFolder = $request->query->get('tree-view') ? $folder->getDossierParent() : $initialParentFolder;

        return $this->getFolderPageRedirection($folder, $destinationFolder);
    }

    #[Route(
        path: 'folder/{id}/toggle-visibility',
        name: 'folder_toggle_visibility',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'PATCH'],
    )]
    #[IsGranted('TOGGLE_VISIBILITY', 'folder')]
    public function toggleVisibility(Request $request, Dossier $folder, FolderManager $manager): Response
    {
        $manager->toggleVisibility($folder);

        return $request->isXmlHttpRequest()
            ? new JsonResponse($folder)
            : $this->redirectToRoute('list_documents', ['id' => $folder->getBeneficiaireId()]);
    }

    #[Route(
        path: 'folder/{id}/create-subfolder',
        name: 'folder_create_subfolder',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('UPDATE', 'parentFolder')]
    public function createSubFolder(
        Request $request,
        Dossier $parentFolder,
        EntityManagerInterface $em,
        FolderManager $manager
    ): Response {
        $folder = Dossier::createFromParent($parentFolder);

        $form = $this->createForm(FolderType::class, $folder, [
            'action' => $this->generateUrl('folder_create_subfolder', ['id' => $parentFolder->getId()]),
            'rename_only' => true,
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($folder);
            $em->flush();

            return $this->redirectToRoute('folder', ['id' => $parentFolder->getId()]);
        }

        return $this->render('v2/vault/folder/create.html.twig', [
            'form' => $form,
            'beneficiary' => $parentFolder->getBeneficiaire(),
            'autocompleteNames' => $manager->getAutocompleteFolderNames(),
        ]);
    }

    #[Route(path: 'folder/{id}/delete', name: 'folder_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'folder')]
    public function delete(Dossier $folder, FolderManager $manager): Response
    {
        $parentFolder = $folder->getDossierParent();
        $manager->delete($folder);

        return $this->getFolderPageRedirection($folder, $parentFolder);
    }

    #[Route(path: 'folder/{id}/detail', name: 'folder_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'folder')]
    public function detail(Dossier $folder): Response
    {
        return $this->render('v2/vault/folder/detail.html.twig', [
            'folder' => $folder,
            'beneficiary' => $folder->getBeneficiaire(),
        ]);
    }

    #[Route(path: 'folder/{id}/download', name: 'folder_download', methods: ['GET'])]
    #[IsGranted('UPDATE', 'folder')]
    public function download(Dossier $folder, FolderManager $manager): Response
    {
        if (!$streamedResponse = $manager->getZipFromFolder($folder)) {
            $this->addFlash('error', 'error_during_download');
            $parentFolder = $folder->getDossierParent();

            return $this->getFolderPageRedirection($folder, $parentFolder);
        }

        return $streamedResponse;
    }

    #[Route(
        path: 'folder/{id}/tree-view-move',
        name: 'folder_tree_view_move',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    #[IsGranted('UPDATE', 'folder')]
    public function treeViewMove(Dossier $folder, FolderManager $folderManager): Response
    {
        $beneficiary = $folder->getBeneficiaire();

        return $this->render('v2/vault/folder/tree_view.html.twig', [
            'folders' => $folderManager->getRootFolders($beneficiary),
            'element' => $folder,
            'beneficiary' => $beneficiary,
        ]);
    }

    private function getFolderPageRedirection(Dossier $folder, ?Dossier $parentFolder): Response
    {
        return $parentFolder
            ? $this->redirectToRoute('folder', ['id' => $parentFolder->getId()])
            : $this->redirectToRoute('list_documents', ['id' => $folder->getBeneficiaireId()]);
    }
}
