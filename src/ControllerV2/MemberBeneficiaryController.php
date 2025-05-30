<?php

namespace App\ControllerV2;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\Contact;
use App\FormV2\FirstMemberVisitType;
use App\ManagerV2\MemberBeneficiaryManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MemberBeneficiaryController extends AbstractController
{
    #[Route(
        path: 'beneficiary/{id}/first-member-visit',
        name: 'first_member_visit',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
        condition: 'request.isXmlHttpRequest()',
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function firstMemberVisit(Beneficiaire $beneficiary, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if ($user && !$user->isMembre()) {
            return new JsonResponse(null, Response::HTTP_UNAUTHORIZED);
        }

        if (!$user?->getNom() || !$user->getPrenom()) {
            $this->addFlash('error', 'error');

            return new JsonResponse(null, Response::HTTP_UNAUTHORIZED);
        }

        $formData = (array) $request->request->all()['first_member_visit'];
        $contact = (new Contact($beneficiary))
            ->setNom($user->getNom())
            ->setPrenom($user->getPrenom())
            ->setTelephone(array_key_exists('sharePhone', $formData) ? $user->getTelephone() : null)
            ->setEmail(array_key_exists('shareMail', $formData) ? $user->getEmail() : null);

        $em->persist($contact);
        $em->flush();
        $this->addFlash('success', 'personal_phone_added_successfully');

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    public function firstMemberVisitNotification(Beneficiaire $beneficiary, MemberBeneficiaryManager $memberBeneficiaryManager): Response
    {
        if (!$memberBeneficiaryManager->isFirstMemberVisit($beneficiary)) {
            return $this->render('void.html.twig');
        }

        return $this->render('v2/notifications/first_visit_notifications.html.twig', [
            'form' => $this->createForm(FirstMemberVisitType::class, null, [
                'action' => $this->generateUrl('first_member_visit', ['id' => $beneficiary->getId()]),
            ]),
        ]);
    }
}
