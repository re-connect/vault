<?php

namespace App\ControllerV2;

use App\Entity\Centre;
use App\ManagerV2\RelayManager;
use App\Repository\CentreRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route(path: '/relays')]
class RelayController extends AbstractController
{
    #[Route(path: '/mine', name: 'my_relays', methods: ['GET'])]
    public function relays(CentreRepository $repository): Response
    {
        $user = $this->getUser();

        return $this->render('v2/vault/relay/index.html.twig', [
            'beneficiary' => $user->getSubjectBeneficiaire(),
            'relays' => $repository->findUserRelays($user),
            'pendingRelays' => $repository->findUserRelays($user, false),
        ]);
    }

    #[Route(path: '/{id}/accept', name: 'accept_relay', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function acceptRelay(Request $request, Centre $relay, RelayManager $manager): Response
    {
        $manager->acceptRelay($this->getUser(), $relay);
        $this->addFlash('success', 'user.pendingCentre.flashAccepter');

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route(path: '/{id}/deny', name: 'deny_relay', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function denyInvitation(Request $request, Centre $relay, RelayManager $manager): Response
    {
        $manager->leaveRelay($this->getUser(), $relay);
        $this->addFlash('success', 'user.pendingCentre.flashRefuser');

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route(path: '/{id}/leave', name: 'leave_relay', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function leave(Request $request, Centre $relay, RelayManager $manager): Response
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('leave_relay', ['id' => $relay->getId()]))
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->leaveRelay($this->getUser(), $relay);
            $this->addFlash('success', 'centre.vousAvezBienQuitte');

            return $this->redirectToRoute('my_relays');
        }

        return $this->render('v2/vault/relay/leave.html.twig', ['form' => $form, 'relay' => $relay]);
    }
}
