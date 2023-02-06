<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\Entity\Contact;
use App\FormV2\FirstMemberVisitType;
use App\ManagerV2\MemberBeneficiaryManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        if (!$user->isMembre()) {
            return new JsonResponse(null, 401);
        }

        $formData = $request->request->all()['first_member_visit'];
        $contact = (new Contact($beneficiary))
            ->setNom($user->getNom())
            ->setPrenom($user->getPrenom())
            ->setTelephone(array_key_exists('sharePhone', $formData) ? $user->getTelephone() : null)
            ->setEmail(array_key_exists('shareMail', $formData) ? $user->getEmail() : null);

        $em->persist($contact);
        $em->flush();
        $this->addFlash('success', 'membre.partageContact.success');

        return new JsonResponse(null, 201);
    }

    #[Route(
        path: 'beneficiary/{id}/first-member-visit-notification',
        name: 'first_member_visit_notification',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function firstMemberVisitNotification(
        Beneficiaire $beneficiary,
        MemberBeneficiaryManager $memberBeneficiaryManager
    ): Response {
        $firstVisitForm = null;
        if ($isFirstVisit = $memberBeneficiaryManager->isFirstMemberVisit($beneficiary)) {
            $memberBeneficiaryManager->handleFirstMemberVisit($beneficiary);
            $firstVisitForm = $this->createForm(FirstMemberVisitType::class, null, [
                'action' => $this->generateUrl('first_member_visit', ['id' => $beneficiary->getId()]),
            ]);
        }

        return $this->renderForm('v2/notifications/first_visit_notifications.html.twig', [
            'isFirstVisit' => $isFirstVisit,
            'firstVisitForm' => $firstVisitForm,
        ]);
    }
}
