<?php

namespace App\ControllerV2;

use App\Entity\Note;
use App\FormV2\NoteType;
use App\ManagerV2\NoteManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NoteController extends AbstractController
{
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

            return $this->redirectToRoute('list_notes', ['id' => $beneficiary->getId()]);
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

        return $this->redirectToRoute('list_notes', ['id' => $note->getBeneficiaireId()]);
    }

    #[Route(
        path: 'note/{id}/toggle-visibility',
        name: 'note_toggle_visibility',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'PATCH'],
    )]
    #[IsGranted('TOGGLE_VISIBILITY', 'note')]
    public function toggleVisibility(Request $request, Note $note, NoteManager $manager): Response
    {
        $manager->toggleVisibility($note);

        return $request->isXmlHttpRequest()
            ? new JsonResponse($note)
            : $this->redirectToRoute('list_notes', ['id' => $note->getBeneficiaireId()]);
    }
}
