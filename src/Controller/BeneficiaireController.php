<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\SetQuestionSecreteType;
use App\Manager\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BeneficiaireController extends REController
{
    public function accueil(): Response
    {
        return $this->render('user/beneficiaire/accueil.html.twig', [
            'beneficiaire' => $this->getUser()->getSubjectBeneficiaire(),
        ]);
    }

    public function setQuestionSecrete(
        Request $request,
        EntityManagerInterface $em,
        UserManager $userManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $beneficiary = $user->getSubjectBeneficiaire();
        $form = $this->createForm(SetQuestionSecreteType::class, $beneficiary);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userManager->updatePassword($user);
            $em->flush();

            $this->successFlash('setQuestionSecrete.bienSauvegarde');

            return $this->redirect($this->generateUrl('re_user_redirectUser'));
        }

        return $this->render('user/beneficiaire/set-question-secrete.html.twig', [
            'form' => $form,
            'beneficiaire' => $beneficiary,
        ]);
    }
}
