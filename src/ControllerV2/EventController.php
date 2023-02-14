<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\Entity\Evenement;
use App\FormV2\EventType;
use App\FormV2\SearchType;
use App\ManagerV2\EventManager;
use App\Repository\EvenementRepository;
use App\ServiceV2\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route(
        path: '/beneficiary/{id}/events',
        name: 'event_list',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function list(
        Request $request,
        Beneficiaire $beneficiary,
        EvenementRepository $repository,
        PaginatorService $paginator,
    ): Response {
        $searchForm = $this->createForm(SearchType::class);

        return $this->renderForm('v2/vault/event/index.html.twig', [
            'beneficiary' => $beneficiary,
            'events' => $paginator->create(
                $this->isLoggedInUser($beneficiary->getUser())
                    ? $repository->findFutureEventsByBeneficiary($beneficiary)
                    : [],
                $request->query->getInt('page', 1),
            ),
            'form' => $searchForm,
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/events/search',
        name: 'event_search',
        requirements: ['id' => '\d+'],
        methods: ['GET'], condition: 'request.isXmlHttpRequest()',
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function search(
        Request $request,
        Beneficiaire $beneficiary,
        EvenementRepository $repository,
        PaginatorService $paginator
    ): Response {
        $word = $request->query->get('word', '');
        $searchForm = $this->createForm(SearchType::class);

        return new JsonResponse([
            'html' => $this->renderForm('v2/vault/event/_list.html.twig', [
                'events' => $paginator->create(
                    $this->isLoggedInUser($beneficiary->getUser())
                        ? $repository->searchFutureEventsByBeneficiary($beneficiary, $word)
                        : $repository->searchSharedFutureEventsByBeneficiary($beneficiary, $word),
                    $request->query->getInt('page', 1),
                ),
                'beneficiary' => $beneficiary,
                'form' => $searchForm,
            ])->getContent(),
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/event/create',
        name: 'event_create',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function create(Request $request, Beneficiaire $beneficiary, EntityManagerInterface $em): Response
    {
        $event = new Evenement($beneficiary);
        $form = $this->createForm(EventType::class, $event, [
            'action' => $this->generateUrl('event_create', ['id' => $beneficiary->getId()]),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'event_created');

            return $this->redirectToRoute('event_list', ['id' => $beneficiary->getId()]);
        }

        return $this->renderForm('v2/vault/event/form.html.twig', [
            'form' => $form,
            'beneficiary' => $beneficiary,
            'event' => $event,
        ]);
    }

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

            return $this->redirectToRoute('event_list', ['id' => $beneficiary->getId()]);
        }

        return $this->renderForm('v2/vault/event/form.html.twig', [
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

        return $this->redirectToRoute('event_list', ['id' => $event->getBeneficiaireId()]);
    }

    #[Route(
        path: 'event/{id}/toggle-visibility',
        name: 'event_toggle_visibility',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'PATCH'],
    )]
    #[IsGranted('UPDATE', 'event')]
    public function toggleVisibility(Request $request, Evenement $event, EventManager $manager): Response
    {
        $manager->toggleVisibility($event);

        return $request->isXmlHttpRequest()
            ? new Response(null, 204)
            : $this->redirectToRoute('event_list', ['id' => $event->getBeneficiaireId()]);
    }
}
