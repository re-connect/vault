<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Entity\PasswordResetSecretQuestion;
use App\Form\Entity\PasswordResetSMS;
use App\Form\Type\PasswordResetSecretQuestionType;
use App\Form\Type\PasswordResetSMSType;
use App\Manager\UserManager;
use App\ManagerV2\UserManager as UserManagerV2;
use App\RepositoryV2\ResetPasswordRequestRepository;
use App\ServiceV2\ResettingService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(options={"expose"=true})
 */
class PrivateResettingController extends AbstractController
{
    #[Route(path: '/user/{id}/reset-password', name: 'private_reset_password', methods: ['GET'])]
    #[IsGranted('gestion beneficiaire', 'userToReset')]
    public function privateResetPassword(User $userToReset): Response
    {
        return $this->render('user/resetting/private/index.html.twig', [
            'userToReset' => $userToReset,
        ]);
    }

    #[Route(path: '/user/{id}/reset-password/email', name: 'private_reset_password_email', methods: ['GET', 'POST'])]
    #[IsGranted('gestion beneficiaire', 'userToReset')]
    public function privateResetPasswordEmail(Request $request, ResettingService $service, User $userToReset): Response
    {
        $service->processSendingPasswordResetEmail($userToReset->getEmail(), $request->getLocale());

        return $this->render('user/resetting/private/index.html.twig', [
            'userToReset' => $userToReset,
        ]);
    }

    #[Route(path: '/user/{id}/reset-password/sms', name: 'private_reset_password_sms', methods: ['GET', 'POST'])]
    #[IsGranted('gestion beneficiaire', 'userToReset')]
    public function privateResetPasswordSms(Request $request, User $userToReset, ResettingService $service, UserManagerV2 $userManager): Response
    {
        $formData = new PasswordResetSMS();
        $form = $this->createForm(PasswordResetSMSType::class, $formData, ['user' => $userToReset])
            ->handleRequest($request);
        $phone = $userToReset->getTelephone();

        if (Request::METHOD_GET === $request->getMethod()) {
            $user = $service->processSendingPasswordResetSms($phone);
            if ($user && !$service->isRequestingBySMS($user)) {
                $this->addFlash('danger', 'reset_password_requested_by_email');

                return $this->redirectToRoute('private_reset_password', ['id' => $user->getId()]);
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $smsCode = $form->get('smsCode')->getData();
            $passwordRequest = $service->findPasswordRequestWithSmsCode($smsCode);
            if ($passwordRequest && $passwordRequest->getUser() === $userToReset) {
                $service->removePasswordRequest($passwordRequest);
                $userManager->updatePassword($userToReset, $form->get('password')->getData());
                $this->addFlash('success', 'public_reset_password_success');

                return $this->redirectToRoute('private_reset_password', ['id' => $userToReset->getId()]);
            }
            $this->addFlash('error', 'public_reset_password_SMS_wrong_code');
        }

        return $this->renderForm('user/resetting/private/sms.html.twig', [
            'userToReset' => $userToReset,
            'form' => $form,
        ]);
    }

    #[Route(path: '/user/{id}/reset-password/question', name: 'private_reset_password_question', methods: ['GET', 'POST'])]
    #[IsGranted('gestion beneficiaire', 'userToReset')]
    public function privateResetPasswordQuestion(Request $request, UserManager $manager, User $userToReset): Response
    {
        $formData = new PasswordResetSecretQuestion();
        $form = $this->createForm(PasswordResetSecretQuestionType::class, $formData, ['user' => $userToReset])
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->changePassword($userToReset, $formData->getPassword());
            $this->addFlash('success', 'public_reset_password_success');

            return $this->redirectToRoute('private_reset_password', ['id' => $userToReset->getId()]);
        }

        return $this->renderForm('user/resetting/private/question.html.twig', [
            'userToReset' => $userToReset,
            'form' => $form,
        ]);
    }

    #[Route(path: '/user/{id}/reset-password/random', name: 'private_reset_password_random', methods: ['GET', 'POST'])]
    #[IsGranted('gestion beneficiaire', 'userToReset')]
    public function privateResetPasswordRandom(Request $request, User $userToReset, UserManager $manager): Response
    {
        $newPassword = null;
        $form = $this->createFormBuilder()->add('submit', SubmitType::class, [
            'attr' => ['class' => 'btn-green btn-blue'],
            'label' => 'user.reinitialiserMdp.reinitialisation',
        ])->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $manager->randomPassword();
            $manager->changePassword($userToReset, $newPassword);
            $this->addFlash('success', 'public_reset_password_success');
        }

        return $this->renderForm('user/resetting/private/random.html.twig', [
            'userToReset' => $userToReset,
            'form' => $form,
            'newPassword' => $newPassword,
        ]);
    }

    #[Route(path: '/user/{id}/unlock-password-reset', name: 'unlock_password_reset', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function unlock(
        Request $request,
        User $user,
        ResetPasswordRequestRepository $repository,
        EntityManagerInterface $em
    ): RedirectResponse {
        foreach ($repository->findBy(['user' => $user]) as $resetPasswordRequest) {
            $em->remove($resetPasswordRequest);
        }
        $em->flush();

        return $request->headers->get('referer')
            ? $this->redirect($request->headers->get('referer'))
            : $this->redirectToRoute('re_main_accueil');
    }
}
