<?php

namespace App\Controller;

use App\Entity\Centre;
use App\Entity\User;
use App\Form\Type\BeneficiaireParametresType;
use App\Form\Type\ChangePasswordType;
use App\Form\Type\GestionnaireParametresType;
use App\Form\Type\UserWithoutPasswordType;
use App\Manager\CentreManager;
use App\Manager\UserManager;
use App\Security\Authorization\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends REController
{
    public function firstVisit(): Response
    {
        return $this->render('user/user/firstVisit.html.twig');
    }

    public function accepterCentre(Centre $centre, CentreManager $centreManager): RedirectResponse
    {
        $centreManager->accepterCentre($this->getUser()->getSubject(), $centre);
        $this->successFlashTranslate('user.pendingCentre.flashAccepter');

        return $this->redirect($this->request->headers->get('referer'));
    }

    public function refuserCentre(Centre $centre, CentreManager $centreManager): RedirectResponse
    {
        $centreManager->refuserCentre($this->getUser()->getSubject(), $centre);
        $this->errorFlashTranslate('user.pendingCentre.flashRefuser');

        return $this->redirect($this->request->headers->get('referer'));
    }

    public function parametres(User $user, EntityManagerInterface $entityManager, UserManager $userManager): Response
    {
        if (false === $this->isGranted(UserVoter::GESTION_USER, $user)) {
            throw new AccessDeniedException("Vous n'avez pas le droit de changer les paramètres de cet utilisateur");
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException();
        } elseif ($user->isBeneficiaire()) {
            $subject = $user->getSubjectBeneficiaire();
            $form = $this->createForm(BeneficiaireParametresType::class, $subject);
        } elseif ($user->isMembre() || $user->isAdministrateur()) {
            $form = $this->createForm(UserWithoutPasswordType::class, $user)->remove('adresse');
        } elseif ($user->isGestionnaire()) {
            $form = $this->createForm(GestionnaireParametresType::class, $user->getSubject(), [
                'gestionnaire' => $user->getSubject(),
            ]);
            $form->add('submit', SubmitType::class, ['label' => 'confirm', 'attr' => ['class' => 'btn']]);
        } else {
            throw new \RuntimeException('Unhandled user');
        }

        $formChangePassword = $this->createForm(ChangePasswordType::class, $user);

        $form->handleRequest($this->request);
        $formChangePassword->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->successFlashTranslate('settings_saved_successfully');

            return $this->redirect($this->request->headers->get('referer'));
        }

        if ($formChangePassword->get('submit')->isClicked()) {
            if (!$userManager->testPassword($user, $formChangePassword->get('currentPassword')->getData())) {
                $formChangePassword->get('currentPassword')->addError(new FormError('wrong_current_password'));
            }

            if ($formChangePassword->isValid()) {
                $user->setPlainPassword($formChangePassword->get('plainPassword')->getData());
                $userManager->updatePassword($user);
                $entityManager->flush();
                $this->successFlashTranslate('password_updated_successfully');

                return $this->redirect($this->request->headers->get('referer'));
            }
        }

        return $this->render('user/user/parametres.html.twig', [
            'form' => $form,
            'formChangePassword' => $formChangePassword,
            'user' => $user,
            'subject' => $user->getSubject(),
        ]);
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

    /**
     * @return RedirectResponse|Response
     */
    public function supprimerCompte(User $user, RequestStack $requestStack, TokenStorageInterface $tokenStorage)
    {
        if (false === $this->isGranted(UserVoter::GESTION_USER, $user)) {
            throw new AccessDeniedException("Vous n'avez pas le droit de supprimer cet utilisateur");
        }

        if ($this->request->isMethod(Request::METHOD_POST)) {
            if ($user->isBeneficiaire()) {
                $this->entityManager->remove($user->getSubjectBeneficiaire());
                $this->entityManager->remove($user);
                $this->entityManager->flush();

                $requestStack->getSession()->invalidate();
                $tokenStorage->setToken();
            }

            return $this->redirectToRoute('re_main_accueil');
        }

        return $this->render('user/user/supprimerCompte.html.twig', [
            'user' => $user,
        ]);
    }
}
