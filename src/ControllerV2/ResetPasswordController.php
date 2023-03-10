<?php

namespace App\ControllerV2;

use App\Entity\User;
use App\FormV2\ChangePasswordFormType;
use App\FormV2\ResetPasswordRequestFormType;
use App\FormV2\ResetPasswordSmsCheckFormType;
use App\ManagerV2\UserManager;
use App\ServiceV2\ResettingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route(path: '/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
    ) {
    }

    #[Route(path: '/choose', name: 'app_forgot_password_request_choose', methods: ['GET'])]
    public function choice(): Response
    {
        return $this->render('v2/reset_password/choice.html.twig');
    }

    #[Route(path: '/email', name: 'app_forgot_password_email_request', methods: ['GET', 'POST'])]
    public function emailRequest(Request $request, ResettingService $service): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $request->getLocale(),
            );
        }

        return $this->renderForm('v2/reset_password/request.html.twig', ['form' => $form]);
    }

    #[Route(path: '/sms', name: 'app_forgot_password_sms_request', methods: ['GET', 'POST'])]
    public function phoneRequest(Request $request, ResettingService $service): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class, null, [
            'sms' => true,
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $service->processSendingPasswordResetSms($form->get('telephone')->getData());
            if ($user) {
                if ($service->isRequestingBySMS($user)) {
                    $form = $this->createForm(ResetPasswordSmsCheckFormType::class, null, [
                        'action' => $this->generateUrl('app_forgot_password_check_sms'),
                        'phone' => $user->getTelephone(),
                    ])->handleRequest($request);
                } else {
                    $this->addFlash('danger', 'reset_password_requested_by_email');
                }
            }
        }

        return $this->renderForm('v2/reset_password/request.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/check-sms', name: 'app_forgot_password_check_sms', methods: ['GET', 'POST'])]
    public function checkSms(Request $request, ResettingService $service): Response
    {
        if (!$requestFormParameters = $request->get('reset_password_sms_check_form')) {
            $this->addFlash('danger', 'error');

            return $this->redirectToRoute('app_forgot_password_sms_request');
        }

        $form = $this->createForm(ResetPasswordSmsCheckFormType::class, null, [
            'phone' => $requestFormParameters['phone'],
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $phone = $form->get('phone')->getData();
            $smsCode = $form->get('smsCode')->getData();
            if ($service->isSmsCheckValid($smsCode, $phone)) {
                $passwordRequest = $service->findPasswordRequestWithSmsCode($smsCode);

                return $this->redirectToRoute('app_reset_password_sms', ['token' => $passwordRequest->getSmsToken()]);
            }

            $this->addFlash('danger', 'public_reset_password_SMS_wrong_code');
        }

        return $this->renderForm('v2/reset_password/request.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/reset/sms/{token}', name: 'app_reset_password_sms', methods: ['GET', 'POST'])]
    public function resetSms(Request $request, ResettingService $service, UserManager $userManager, string $token = null): Response
    {
        if ($token) {
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password_sms');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        $passwordRequest = $service->findPasswordRequestWithSmsToken($token);

        if (!$passwordRequest) {
            $this->addFlash('danger', 'error');

            return $this->redirectToRoute('app_forgot_password_sms_request');
        }

        if ($passwordRequest->isExpired()) {
            $this->addFlash('danger', 'public_reset_password_failure_not_in_time');

            return $this->redirectToRoute('app_forgot_password_sms_request');
        }

        /** @var User $user */
        $user = $passwordRequest->getUser();

        $form = $this->createForm(ChangePasswordFormType::class, null, ['isBeneficiaire' => $user->isBeneficiaire()])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->removePasswordRequest($passwordRequest);
            $userManager->updatePassword($user, $form->get('plainPassword')->getData());
            $this->cleanSessionAfterReset();
            $this->addFlash('success', 'public_reset_password_success');

            return $this->redirectToRoute('re_main_login');
        }

        return $this->renderForm('v2/reset_password/reset.html.twig', [
            'form' => $form,
            'isBeneficiaire' => $user->isBeneficiaire(),
        ]);
    }

    #[Route(path: '/reset/email/{token}', name: 'app_reset_password_email', methods: ['GET', 'POST'])]
    public function resetEmail(Request $request, UserManager $userManager, string $token = null): Response
    {
        if ($token) {
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password_email');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash(
                'danger',
                $e instanceof ExpiredResetPasswordTokenException
                    ? 'public_reset_password_failure_not_in_time'
                    : 'error'
            );

            return $this->redirectToRoute('app_forgot_password_email_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class, null, ['isBeneficiaire' => $user->isBeneficiaire()])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);
            $userManager->updatePassword($user, $form->get('plainPassword')->getData());
            $this->cleanSessionAfterReset();
            $this->addFlash('success', 'public_reset_password_success');

            return $this->redirectToRoute('re_main_login');
        }

        return $this->renderForm('v2/reset_password/reset.html.twig', [
            'form' => $form,
            'isBeneficiaire' => $user->isBeneficiaire(),
        ]);
    }
}
