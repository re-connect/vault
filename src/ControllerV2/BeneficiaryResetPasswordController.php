<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\FormV2\ResetPassword\BeneficiaryRequest\ResetPasswordSmsFormModel;
use App\FormV2\ResetPassword\BeneficiaryRequest\ResetPasswordSmsFormType;
use App\ServiceV2\ResettingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_MEMBRE')]
class BeneficiaryResetPasswordController extends AbstractController
{
    #[IsGranted('UPDATE', 'beneficiary')]
    #[Route(path: '/beneficiary/{id}/reset-password', name: 'reset_password_beneficiary', methods: ['GET'])]
    public function choice(Beneficiaire $beneficiary): Response
    {
        return $this->render('v2/reset_password/beneficiary/choice.html.twig', [
            'beneficiary' => $beneficiary,
        ]);
    }

    #[IsGranted('UPDATE', 'beneficiary')]
    #[Route(path: '/beneficiary/{id}/reset-password/email', name: 'reset_password_beneficiary_email', methods: ['GET'])]
    public function resetEmail(Request $request, Beneficiaire $beneficiary, ResettingService $service): Response
    {
        if ($email = $beneficiary->getUser()->getEmail()) {
            $service->processSendingPasswordResetEmail($email, $request->getLocale());
        } else {
            $this->addFlash('error', 'beneficiary_has_no_email');
        }

        return $this->render('v2/reset_password/beneficiary/choice.html.twig', [
            'beneficiary' => $beneficiary,
        ]);
    }

    #[IsGranted('UPDATE', 'beneficiary')]
    #[Route(path: '/beneficiary/{id}/reset-password/sms', name: 'reset_password_beneficiary_sms', methods: ['GET', 'POST'])]
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
        $service->processSendingBeneficiaryPasswordResetSms($beneficiary);

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
}
