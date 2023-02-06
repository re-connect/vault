<?php

namespace App\ControllerV2;

use App\FormV2\ChangePasswordFormType;
use App\ManagerV2\UserManager;
use App\ServiceV2\GdprService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GdprController extends AbstractController
{
    #[Route(path: '/update-password', name: 'app_update_password', methods: ['GET', 'POST'])]
    public function updatePassword(Request $request, UserManager $userManager, GdprService $gdprService): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordFormType::class, null, ['isBeneficiaire' => $user->isBeneficiaire()])->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('plainPassword')->getData();
            if (!$userManager->isPasswordValid($user, $newPassword)) {
                $userManager->updatePassword($user, $newPassword);
                $this->addFlash('success', 'password_updated_successfully');

                return $this->redirectToRoute('re_user_loginEnd');
            }
            $this->addFlash('danger', 'password_new_should_be_different_from_current');
        } else {
            $gdprService->showPasswordRenewalFlash();
        }

        return $this->renderForm('user/update_password.html.twig', [
            'passwordForm' => $form,
        ]);
    }
}
