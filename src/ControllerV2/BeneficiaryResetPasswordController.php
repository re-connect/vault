<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\FormV2\ResetPassword\BeneficiaryRequest\ResetPasswordSecretAnswerFormModel;
use App\FormV2\ResetPassword\BeneficiaryRequest\ResetPasswordSecretAnswerType;
use App\FormV2\ResetPassword\BeneficiaryRequest\ResetPasswordSmsFormModel;
use App\FormV2\ResetPassword\BeneficiaryRequest\ResetPasswordSmsFormType;
use App\ManagerV2\UserManager;
use App\ServiceV2\ResettingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_MEMBRE')]
#[Route(path: '/beneficiaries')]
class BeneficiaryResetPasswordController extends AbstractController
{
    #[IsGranted('UPDATE', 'beneficiary')]
    #[Route(path: '/{id<\d+>}/reset-password', name: 'reset_password_beneficiary', methods: ['GET'])]
    public function choice(Beneficiaire $beneficiary): Response
    {
        return $this->render('v2/reset_password/beneficiary/choice.html.twig', [
            'beneficiary' => $beneficiary,
        ]);
    }

    #[IsGranted('UPDATE', 'beneficiary')]
    #[Route(path: '/{id<\d+>}/reset-password/email', name: 'reset_password_beneficiary_email', methods: ['GET'])]
    public function resetEmail(Beneficiaire $beneficiary, ResettingService $service): Response
    {
        $user = $beneficiary->getUser();
        if ($user->getEmail()) {
            $service->processSendingUserPasswordResetEmail($user);
        } else {
            $this->addFlash('error', 'beneficiary_has_no_email');
        }

        return $this->render('v2/reset_password/beneficiary/choice.html.twig', [
            'beneficiary' => $beneficiary,
        ]);
    }

    #[IsGranted('UPDATE', 'beneficiary')]
    #[Route(path: '/{id<\d+>}/reset-password/sms', name: 'reset_password_beneficiary_sms', methods: ['GET', 'POST'])]
    public function resetSMS(
        Request $request,
        Beneficiaire $beneficiary,
        ResettingService $service,
        TranslatorInterface $translator,
    ): Response {
        if ($errorMessage = $service->getErrorMessage($beneficiary)) {
            $this->addFlash('error', $errorMessage);

            return $this->redirectToRoute('reset_password_beneficiary', ['id' => $beneficiary->getId()]);
        }

        $userToReset = $beneficiary->getUser();
        $service->processSendingUserPasswordResetSms($userToReset);

        $formModel = new ResetPasswordSmsFormModel();
        $form = $this->createForm(ResetPasswordSmsFormType::class, $formModel, [
            'action' => $this->generateUrl('reset_password_beneficiary_sms', ['id' => $beneficiary->getId()]),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $smsCode = $formModel->smsCode;
            if ($service->isSmsCheckValid($smsCode, $userToReset->getTelephone())) {
                $service->resetPassword(
                    $service->findPasswordRequestWithSmsCode($smsCode),
                    $form->get('password')->get('plainPassword')->getData(),
                );
                $this->addFlash('success', $translator->trans('beneficiary_reset_password_success', ['%fullName%' => $userToReset->getFullName()]));

                return $this->redirectToRoute('list_beneficiaries');
            }
            $form->get('smsCode')->addError(new FormError($translator->trans('public_reset_password_SMS_wrong_code')));
        }

        return $this->render('v2/reset_password/beneficiary/sms.html.twig', [
            'beneficiary' => $beneficiary,
            'form' => $form,
        ]);
    }

    #[IsGranted('UPDATE', 'beneficiary')]
    #[Route(
        path: '/{id<\d+>}/reset-password/secret-answer',
        name: 'reset_password_beneficiary_secret_answer',
        methods: ['GET', 'POST'],
    )]
    public function resetSecretAnswer(
        Request $request,
        Beneficiaire $beneficiary,
        UserManager $userManager,
        TranslatorInterface $translator,
    ): Response {
        if (!$beneficiary->getReponseSecrete()) {
            return $this->redirectToRoute('reset_password_beneficiary', ['id' => $beneficiary->getId()]);
        }

        $formModel = new ResetPasswordSecretAnswerFormModel($beneficiary);
        $form = $this->createForm(ResetPasswordSecretAnswerType::class, $formModel, [
            'action' => $this->generateUrl('reset_password_beneficiary_secret_answer', ['id' => $beneficiary->getId()]),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userToReset = $beneficiary->getUser();
            $userManager->updatePassword($userToReset, $form->get('password')->get('plainPassword')->getData());
            $this->addFlash('success', $translator->trans('beneficiary_reset_password_success', ['%fullName%' => $userToReset->getFullName()]));

            return $this->redirectToRoute('list_beneficiaries');
        }

        return $this->render('v2/reset_password/beneficiary/secret_answer.html.twig', [
            'beneficiary' => $beneficiary,
            'form' => $form,
        ]);
    }
}
