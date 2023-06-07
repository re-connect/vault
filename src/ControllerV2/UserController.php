<?php

namespace App\ControllerV2;

use App\FormV2\ChangePasswordFormType;
use App\FormV2\UserSettingsType;
use App\ManagerV2\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/user')]
class UserController extends AbstractController
{
    #[Route(path: '/settings', name: 'user_settings', methods: ['GET', 'POST'])]
    public function settings(
        Request $request,
        EntityManagerInterface $em,
        UserManager $userManager,
        TranslatorInterface $translator,
    ): Response {
        $user = $this->getUser();

        if (!$this->isGranted('SELF_EDIT', $user)) {
            throw new AccessDeniedException();
        }

        $userForm = $this->createForm(UserSettingsType::class, $user)->handleRequest($request);

        $passwordForm = $this->createForm(ChangePasswordFormType::class, null, [
            'checkCurrentPassword' => true,
            'isBeneficiaire' => $user->isBeneficiaire(),
        ])->handleRequest($request);

        if ($userForm->isSubmitted()) {
            if ($userForm->isValid()) {
                $em->flush();
                $this->addFlash('success', 'settings_saved_successfully');

                return $this->redirectToRoute('user_settings');
            }
        }

        if ($passwordForm->isSubmitted()) {
            $currentPasswordInput = $passwordForm->get('currentPassword');
            if (!$userManager->isPasswordValid($user, $currentPasswordInput->getData())) {
                $currentPasswordInput->addError(new FormError($translator->trans('wrong_current_password')));
            }
            if ($passwordForm->isValid()) {
                $userManager->updatePassword($user, $passwordForm->get('plainPassword')->getData());
                $this->addFlash('success', 'password_updated_successfully');

                return $this->redirectToRoute('user_settings');
            }
        }

        return $this->renderForm('v2/user/settings.html.twig', [
            'userForm' => $userForm,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route(path: '/delete', name: 'user_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, UserManager $userManager, TokenStorageInterface $tokenStorage): Response
    {
        $user = $this->getUser();

        if (!$this->isGranted('DELETE_BENEFICIARY', $user)) {
            throw new AccessDeniedException();
        }

        $form = $this->createFormBuilder()->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager->remove($user);
            $request->getSession()->invalidate();
            $tokenStorage->setToken(null);

            return $this->redirectToRoute('re_main_login');
        }

        return $this->renderForm('v2/user/delete.html.twig', [
            'submitForm' => $form,
        ]);
    }
}
