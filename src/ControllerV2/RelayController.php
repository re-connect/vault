<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\User;
use App\ManagerV2\RelayManager;
use App\Repository\CentreRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RelayController extends AbstractController
{
    #[Route(
        path: '/beneficiary/{id}/relays',
        name: 'list_relays',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    #[Security("is_granted('SELF_EDIT', beneficiary.getUser())")]
    public function relays(Beneficiaire $beneficiary, CentreRepository $repository): Response
    {
        $user = $beneficiary->getUser();

        return $this->render('v2/vault/relay/index.html.twig', [
            'beneficiary' => $beneficiary,
            'relays' => $repository->findUserRelays($user),
            'pendingRelays' => $repository->findUserRelays($user, false),
        ]);
    }

    #[Route(
        path: '/user/{id}/relay/{relayId}/accept',
        name: 'relay_accept',
        requirements: ['id' => '\d+', 'relayId' => '\d+'],
        methods: ['GET'],
    )]
    #[ParamConverter('relay', class: 'App\Entity\Centre', options: ['id' => 'relayId'])]
    #[IsGranted('SELF_EDIT', 'user')]
    public function acceptInvitation(Request $request, User $user, Centre $relay, RelayManager $manager): Response
    {
        $manager->acceptInvitation($user, $relay);
        $this->addFlash('success', 'user.pendingCentre.flashAccepter');

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route(
        path: '/user/{id}/relay/{relayId}/deny',
        name: 'relay_deny',
        requirements: ['id' => '\d+', 'relayId' => '\d+'],
        methods: ['GET'],
    )]
    #[ParamConverter('relay', class: 'App\Entity\Centre', options: ['id' => 'relayId'])]
    #[IsGranted('SELF_EDIT', 'user')]
    public function denyInvitation(Request $request, User $user, Centre $relay, RelayManager $manager): Response
    {
        $manager->leaveRelay($user, $relay);
        $this->addFlash('success', 'user.pendingCentre.flashRefuser');

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route(
        path: '/beneficiary/{id}/relay/{relayId}/leave',
        name: 'relay_leave',
        requirements: ['id' => '\d+', 'relayId' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[ParamConverter('relay', class: 'App\Entity\Centre', options: ['id' => 'relayId'])]
    #[Security("is_granted('SELF_EDIT', beneficiary.getUser())")]
    public function leave(Request $request, Beneficiaire $beneficiary, Centre $relay, RelayManager $manager): Response
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('relay_leave', [
                'id' => $beneficiary->getId(),
                'relayId' => $relay->getId(),
            ]))
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->leaveRelay($beneficiary->getUser(), $relay);
            $this->addFlash('success', 'centre.vousAvezBienQuitte');

            return $this->redirectToRoute('list_relays', ['id' => $beneficiary->getId()]);
        }

        return $this->renderForm('v2/vault/relay/leave.html.twig', [
            'form' => $form,
            'beneficiary' => $beneficiary,
            'relay' => $relay,
        ]);
    }
}
