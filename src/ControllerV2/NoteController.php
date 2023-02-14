<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\Entity\Note;
use App\FormV2\NoteType;
use App\FormV2\SearchType;
use App\ManagerV2\NoteManager;
use App\Repository\NoteRepository;
use App\ServiceV2\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NoteController extends AbstractController
{
    #[Route(path: '/beneficiary/{id}/notes', name: 'note_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function list(
        Request $request,
        Beneficiaire $beneficiary,
        NoteRepository $repository,
        PaginatorService $paginator,
    ): Response {
        $searchForm = $this->createForm(SearchType::class);

        return $this->renderForm('v2/vault/note/index.html.twig', [
            'beneficiary' => $beneficiary,
            'notes' => $paginator->create(
                $this->isLoggedInUser($beneficiary->getUser())
                    ? $repository->findAllByBeneficiary($beneficiary)
                    : [],
                $request->query->getInt('page', 1),
            ),
            'form' => $searchForm,
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/notes/search',
        name: 'note_search',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
        condition: 'request.isXmlHttpRequest()',
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function search(
        Beneficiaire $beneficiary,
        Request $request,
        NoteRepository $repository,
        PaginatorService $paginator
    ): JsonResponse {
        $word = $request->query->get('word', '');
        $searchForm = $this->createForm(SearchType::class);

        return new JsonResponse([
            'html' => $this->renderForm('v2/vault/note/_list.html.twig', [
                'notes' => $paginator->create(
                    $this->isLoggedInUser($beneficiary->getUser())
                        ? $repository->searchByBeneficiary($beneficiary, $word)
                        : $repository->searchSharedByBeneficiary($beneficiary, $word),
                    $request->query->getInt('page', 1),
                ),
                'beneficiary' => $beneficiary,
                'form' => $searchForm,
            ])->getContent(),
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/note/create',
        name: 'note_create',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function create(Beneficiaire $beneficiary, Request $request, EntityManagerInterface $em): Response
    {
        $note = new Note($beneficiary);
        $form = $this->createForm(NoteType::class, $note, [
            'action' => $this->generateUrl('note_create', ['id' => $beneficiary->getId()]),
            'private' => $this->getUser() === $beneficiary->getUser(),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($note);
            $em->flush();
            $this->addFlash('success', 'note_created');

            return $this->redirectToRoute('note_list', ['id' => $beneficiary->getId()]);
        }

        return $this->renderForm('v2/vault/note/create.html.twig', [
            'form' => $form,
            'beneficiary' => $beneficiary,
        ]);
    }

    #[Route(path: '/note/{id}/detail', name: 'note_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'note')]
    public function detail(Note $note): Response
    {
        return $this->render('v2/vault/note/detail.html.twig', [
            'note' => $note,
            'beneficiary' => $note->getBeneficiaire(),
        ]);
    }

    #[Route(path: '/note/{id}/edit', name: 'note_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('UPDATE', 'note')]
    public function edit(Note $note, Request $request, EntityManagerInterface $em): Response
    {
        $beneficiary = $note->getBeneficiaire();
        $form = $this->createForm(NoteType::class, $note, [
            'action' => $this->generateUrl('note_edit', ['id' => $note->getId()]),
            'private' => $note->getBPrive(),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'note_updated');

            return $this->redirectToRoute('note_list', ['id' => $beneficiary->getId()]);
        }

        return $this->renderForm('v2/vault/note/edit.html.twig', [
            'form' => $form,
            'beneficiary' => $beneficiary,
        ]);
    }

    #[Route(path: '/note/{id}/delete', name: 'note_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'note')]
    public function delete(Note $note, EntityManagerInterface $em): Response
    {
        $em->remove($note);
        $em->flush();
        $this->addFlash('success', 'note.bienSupprime');

        return $this->redirectToRoute('note_list', ['id' => $note->getBeneficiaireId()]);
    }

    #[Route(
        path: 'note/{id}/toggle-visibility',
        name: 'note_toggle_visibility',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'PATCH'],
    )]
    #[IsGranted('UPDATE', 'note')]
    public function toggleVisibility(Request $request, Note $note, NoteManager $manager): Response
    {
        $manager->toggleVisibility($note);

        return $request->isXmlHttpRequest()
            ? new Response(null, 204)
            : $this->redirectToRoute('note_list', ['id' => $note->getBeneficiaireId()]);
    }
}
