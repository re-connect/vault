<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\FormV2\UserCreation\SecretQuestionType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/beneficiary')]
class BeneficiaryController extends AbstractController
{
    #[IsGranted('UPDATE', 'beneficiary')]
    #[Route(path: '/{id}/set_secret_question', name: 'set_secret_question', methods: ['POST'])]
    public function setSecretQuestion(Request $request, Beneficiaire $beneficiary, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SecretQuestionType::class, $beneficiary, [
            'action' => $this->generateUrl('set_secret_question', ['id' => $beneficiary->getId()]),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
        }

        return $this->redirectToRoute('re_user_redirectUser');
    }
}
