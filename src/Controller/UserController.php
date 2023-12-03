<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends REController
{
    public function firstVisit(): Response
    {
        return $this->render('user/user/firstVisit.html.twig');
    }

    public function cgs(TranslatorInterface $translator, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder()
            ->add('accept', CheckboxType::class, ['label' => 'user.cgu.bAccepter'])
            ->add('submit', SubmitType::class, ['label' => 'main.continuer', 'attr' => ['class' => 'btn-green']])
            ->getForm();
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$form->get('accept')->getData()) {
                $form->addError(new FormError($translator->trans('user.cgu.mustAccept')));

                return $this->render('user/user/cgs-cs.html.twig', [
                    'form' => $form,
                ]);
            } else {
                $this->getUser()->setFirstVisit();
                $em->flush();

                return $this->redirect($this->generateUrl('re_user_redirectUser'));
            }
        }

        return $this->render('user/user/cgs-cs.html.twig', [
            'form' => $form,
        ]);
    }
}
