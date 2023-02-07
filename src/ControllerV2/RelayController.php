<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\ManagerV2\RelayManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RelayController extends AbstractController
{
    #[Route(
        path: '/beneficiary/{id}/relays',
        name: 'relay_list',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    #[Security("is_granted('SELF_EDIT', beneficiary.getUser())")]
    public function relays(Beneficiaire $beneficiary, RelayManager $manager): Response
    {
        return $this->render('v2/vault/relay/index.html.twig', [
            'beneficiary' => $beneficiary,
            'relays' => $manager->findPersonalRelays($beneficiary),
            'pendingRelays' => $manager->findPersonalRelays($beneficiary, false),
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/relay/notification',
        name: 'relay_notification',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    public function relayNotification(Beneficiaire $beneficiary, RelayManager $manager, AuthorizationCheckerInterface $checker): Response
    {
        $pendingRelays = $checker->isGranted('SELF_EDIT', $beneficiary->getUser())
            ? $manager->findPersonalRelays($beneficiary, false)
            : [];

        return $this->render('v2/notifications/relay_invitation_notification.html.twig', [
            'pendingRelays' => $pendingRelays,
            'beneficiary' => $beneficiary,
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/relay/{relayId}/accept',
        name: 'relay_accept',
        requirements: ['id' => '\d+', 'relayId' => '\d+'],
        methods: ['GET'],
    )]
    #[ParamConverter('relay', class: 'App\Entity\Centre', options: ['id' => 'relayId'])]
    #[Security("is_granted('SELF_EDIT', beneficiary.getUser())")]
    public function acceptInvitation(Beneficiaire $beneficiary, Centre $relay, RelayManager $manager): Response
    {
        $manager->acceptInvitation($beneficiary, $relay);
        $this->addFlash('success', 'user.pendingCentre.flashAccepter');

        return $this->redirectToRoute('relay_list', ['id' => $beneficiary->getId()]);
    }

    #[Route(
        path: '/beneficiary/{id}/relay/{relayId}/deny',
        name: 'relay_deny',
        requirements: ['id' => '\d+', 'relayId' => '\d+'],
        methods: ['GET'],
    )]
    #[ParamConverter('relay', class: 'App\Entity\Centre', options: ['id' => 'relayId'])]
    #[Security("is_granted('SELF_EDIT', beneficiary.getUser())")]
    public function denyInvitation(Beneficiaire $beneficiary, Centre $relay, RelayManager $manager): Response
    {
        $manager->leaveRelay($beneficiary, $relay);
        $this->addFlash('success', 'user.pendingCentre.flashRefuser');

        return $this->redirectToRoute('relay_list', ['id' => $beneficiary->getId()]);
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
            $manager->leaveRelay($beneficiary, $relay);
            $this->addFlash('success', 'centre.vousAvezBienQuitte');

            return $this->redirectToRoute('relay_list', ['id' => $beneficiary->getId()]);
        }

        return $this->renderForm('v2/vault/relay/leave.html.twig', [
            'form' => $form,
            'beneficiary' => $beneficiary,
            'relay' => $relay,
        ]);
    }
}
