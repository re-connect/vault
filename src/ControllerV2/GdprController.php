<?php

namespace App\ControllerV2;

use App\Entity\User;
use App\FormV2\ChangePasswordFormType;
use App\ManagerV2\UserManager;
use App\ServiceV2\GdprService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GdprController extends AbstractController
{
    #[Route(path: '/update-password', name: 'app_update_password', methods: ['GET', 'POST'])]
    public function updatePassword(Request $request, UserManager $userManager, GdprService $gdprService): Response
    {
        $user = $this->getUser();
        $form = $this->createPasswordForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $user) {
            $newPassword = $form->get('plainPassword')->getData();
            if (!$userManager->isPasswordValid($user, $newPassword)) {
                $userManager->updatePassword($user, $newPassword);
                $this->addFlash('success', 'password_updated_successfully');

                return $this->redirectToRoute('login_end');
            }
            $this->addFlash('danger', 'password_new_should_be_different_from_current');
        } else {
            $gdprService->showPasswordRenewalFlash();
        }

        return $this->renderPasswordPage($user, $form);
    }

    #[Route(path: '/improve-password', name: 'improve_password', methods: ['GET', 'POST'])]
    public function improvePassword(Request $request, UserManager $userManager): Response
    {
        $user = $this->getUser();
        $form = $this->createPasswordForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $user) {
            $user->setHasPasswordWithLatestPolicy(true);
            $userManager->updatePassword($user, $form->get('plainPassword')->getData());
            $this->addFlash('success', 'password_updated_successfully');

            return $this->redirectToRoute('redirect_user');
        }

        return $this->renderPasswordPage($user, $form);
    }

    private function createPasswordForm(): FormInterface
    {
        return $this->createForm(ChangePasswordFormType::class);
    }

    private function renderPasswordPage(?User $user, FormInterface $form): Response
    {
        return $this->render('v2/user/update_password_form.html.twig', [
            'user' => $user,
            'passwordForm' => $form,
        ]);
    }
}
