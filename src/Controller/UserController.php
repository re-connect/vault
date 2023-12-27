<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends REController
{
    #[Route(path: '/user/first-visit', name: 'user_first_visit', methods: ['GET'])]
    public function firstVisit(): Response
    {
        return $this->render('user/user/firstVisit.html.twig');
    }

    #[Route(path: '/user/cgs', name: 'user_cgs', methods: ['GET'])]
    public function cgs(TranslatorInterface $translator, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder()
            ->add('accept', CheckboxType::class, ['label' => 'accept_terms_of_use'])
            ->add('submit', SubmitType::class, ['label' => 'continue', 'attr' => ['class' => 'btn-green']])
            ->getForm();
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$form->get('accept')->getData()) {
                $form->addError(new FormError($translator->trans('you_must_accept_terms_of_use')));

                return $this->render('user/user/cgs-cs.html.twig', [
                    'form' => $form,
                ]);
            } else {
                $this->getUser()->setFirstVisit();
                $em->flush();

                return $this->redirect($this->generateUrl('redirect_user'));
            }
        }

        return $this->render('user/user/cgs-cs.html.twig', [
            'form' => $form,
        ]);
    }
}
