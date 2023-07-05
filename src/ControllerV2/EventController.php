<?php

namespace App\ControllerV2;

use App\Entity\Evenement;
use App\FormV2\EventType;
use App\ManagerV2\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EventController extends AbstractController
{
    #[Route(path: '/event/{id}/edit', name: 'event_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('UPDATE', 'event')]
    public function edit(Request $request, Evenement $event, EventManager $manager): Response
    {
        $beneficiary = $event->getBeneficiaire();
        $form = $this->createForm(EventType::class, $event, [
            'action' => $this->generateUrl('event_edit', ['id' => $event->getId()]),
            'private' => $event->getBPrive(),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->updateReminders($event);
            $this->addFlash('success', 'event_updated');

            return $this->redirectToRoute('list_events', ['id' => $beneficiary->getId()]);
        }

        return $this->render('v2/vault/event/form.html.twig', [
            'form' => $form,
            'beneficiary' => $beneficiary,
            'event' => $event,
        ]);
    }

    #[Route(path: '/event/{id}/detail', name: 'event_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'event')]
    public function detail(Evenement $event): Response
    {
        return $this->render('v2/vault/event/detail.html.twig', [
            'event' => $event,
            'beneficiary' => $event->getBeneficiaire(),
        ]);
    }

    #[Route(path: '/event/{id}/delete', name: 'event_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'event')]
    public function delete(Evenement $event, EntityManagerInterface $em): Response
    {
        $em->remove($event);
        $em->flush();
        $this->addFlash('success', 'evenement.bienSupprime');

        return $this->redirectToRoute('list_events', ['id' => $event->getBeneficiaireId()]);
    }

    #[Route(
        path: 'event/{id}/toggle-visibility',
        name: 'event_toggle_visibility',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'PATCH'],
    )]
    #[IsGranted('TOGGLE_VISIBILITY', 'event')]
    public function toggleVisibility(Request $request, Evenement $event, EventManager $manager): Response
    {
        $manager->toggleVisibility($event);

        return $request->isXmlHttpRequest()
            ? new JsonResponse($event)
            : $this->redirectToRoute('list_events', ['id' => $event->getBeneficiaireId()]);
    }
}
