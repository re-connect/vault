<?php

namespace App\ServiceV2;

use App\Entity\Annotations\ResetPasswordRequest;
use App\Entity\Beneficiaire;
use App\Entity\User;
use App\ManagerV2\UserManager;
use App\RepositoryV2\ResetPasswordRequestRepository;
use App\Service\TokenGeneratorInterface;
use App\ServiceV2\Traits\SessionsAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResettingService
{
    use ResetPasswordControllerTrait;
    use SessionsAwareTrait;

    public function __construct(
        private readonly Mailer\MailerService $mailerService,
        private readonly ResetPasswordRequestRepository $repository,
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly EntityManagerInterface $em,
        private readonly NotificationService $notificator,
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly UserManager $userManager,
        private RequestStack $requestStack,
    ) {
    }

    public function handleEmailSend(User $user, ResetPasswordToken $token): void
    {
        $this->mailerService->sendResetPasswordLink($user, $token);
    }

    public function generateSmsCodeAndToken(User $user): void
    {
        $code = random_int(100000, 999999);
        $token = $this->tokenGenerator->generateToken();

        if ($userRequest = $this->repository->getMostRecentNonExpiredRequest($user)) {
            $userRequest->setSmsCode(strval($code));
            $userRequest->setSmsToken($token);
            $this->em->flush();
        }
    }

    public function getSmsCode(User $user): ?string
    {
        if ($userRequest = $this->repository->getMostRecentNonExpiredRequest($user)) {
            return $userRequest->getSmsCode();
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    public function handleSmsSend(User $user, string $smsCode): void
    {
        $this->notificator->sendSmsResetPassword($user, $smsCode);
    }

    public function isSmsCheckValid(string $smsCode, string $phone): bool
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['telephone' => $phone]);

        return $user && $this->repository->findOneBy(['smsCode' => $smsCode]);
    }

    public function findPasswordRequestWithSmsCode(string $smsCode): ?ResetPasswordRequest
    {
        return $this->repository->findOneBy(['smsCode' => $smsCode]);
    }

    public function findPasswordRequestWithSmsToken(string $smsToken): ?ResetPasswordRequest
    {
        return $this->repository->findOneBy(['smsToken' => $smsToken]);
    }

    public function removePasswordRequest(ResetPasswordRequest $request): void
    {
        $this->repository->removeResetPasswordRequest($request);
    }

    public function resetPassword(ResetPasswordRequest $request, string $password): void
    {
        /** @var User $userToReset * */
        $userToReset = $request->getUser();
        $this->removePasswordRequest($request);
        $this->userManager->updatePassword($userToReset, $password);
    }

    public function isRequestingBySMS(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        $requests = $this->repository->findBy(['user' => $user]);

        return 0 < count($requests) && null !== $requests[0]->getSmsCode();
    }

    public function processSendingPasswordResetEmail(string $email): void
    {
        $userRepository = $this->em->getRepository(User::class);
        $usersCount = $userRepository->count(['email' => $email]);

        if (1 !== $usersCount) {
            $this->addFlashMessage('danger', 0 === $usersCount ? 'resetting.public.existePas' : 'email_duplicate');

            return;
        }

        $user = $userRepository->findOneBy([
            'email' => $email,
        ]);

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            $this->handleEmailSend($user, $resetToken);
            $this->requestStack->getCurrentRequest()->getSession()->set('ResetPasswordPublicToken', $resetToken);
            $this->addFlashMessage('success', 'public_reset_password_email_has_been_sent');
        } catch (TooManyPasswordRequestsException $e) {
            $this->addFlashMessage('danger', 'public_reset_password_already_requested');
            if ($this->isRequestingBySMS($user)) {
                $this->addFlashMessage('danger', 'reset_password_requested_by_SMS');
            }
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlashMessage('danger', 'error');
        }
    }

    public function processSendingPasswordResetSms(string $phone): ?User
    {
        $userRepository = $this->em->getRepository(User::class);
        $usersCount = $userRepository->count(['telephone' => $phone]);

        if (1 !== $usersCount) {
            $this->addFlashMessage(
                'danger',
                0 === $usersCount
                    ? 'phone_does_not_exist'
                    : 'phone_duplicate'
            );

            return null;
        }

        $user = $userRepository->findOneBy([
            'telephone' => $phone,
        ]);
        try {
            // in order to create request
            $this->resetPasswordHelper->generateResetToken($user);
        } catch (TooManyPasswordRequestsException $e) {
            $this->addFlashMessage('danger', 'public_reset_password_already_requested');

            return $user;
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlashMessage('danger', 'error');

            return null;
        }

        $this->generateSmsCodeAndToken($user);
        $smsCode = $this->getSmsCode($user);
        if ($smsCode) {
            try {
                $this->handleSmsSend($user, $smsCode);
                $this->addFlashMessage('success', 'public_reset_password_SMS_has_been_sent');

                return $user;
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }

    public function processSendingUserPasswordResetSms(User $user): void
    {
        try {
            // in order to create request
            $this->resetPasswordHelper->generateResetToken($user);
        } catch (TooManyPasswordRequestsException) {
            return;
        } catch (ResetPasswordExceptionInterface) {
            $this->addFlashMessage('danger', 'error');

            return;
        }

        $this->generateSmsCodeAndToken($user);
        $smsCode = $this->getSmsCode($user);
        if ($smsCode) {
            try {
                $this->handleSmsSend($user, $smsCode);
                $this->addFlashMessage('success', 'beneficiary_reset_password_SMS_has_been_sent');
            } catch (\Exception) {
                $this->addFlashMessage('danger', 'error');

                return;
            }
        }
    }

    public function processSendingUserPasswordResetEmail(User $user): void
    {
        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            $this->handleEmailSend($user, $resetToken);
            $this->requestStack->getCurrentRequest()->getSession()->set('ResetPasswordPublicToken', $resetToken);
            $this->addFlashMessage('success', 'beneficiary_reset_password_email_has_been_sent');
        } catch (TooManyPasswordRequestsException) {
            $this->addFlashMessage('danger', 'beneficiary_reset_password_already_requested');
            if ($this->isRequestingBySMS($user)) {
                $this->addFlashMessage('danger', 'reset_password_requested_by_SMS');
            }
        } catch (ResetPasswordExceptionInterface) {
            $this->addFlashMessage('danger', 'error');
        }
    }

    public function isRequestingByEmail(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        $requests = $this->repository->findBy(['user' => $user]);

        return 0 < count($requests) && null === $requests[0]->getSmsCode();
    }

    public function getErrorMessage(Beneficiaire $beneficiary): ?string
    {
        $user = $beneficiary->getUser();

        if (!$user->getTelephone()) {
            return 'beneficiary_has_no_phone_number';
        } elseif ($this->isRequestingByEmail($user)) {
            return 'beneficiary_reset_password_already_requested_by_email';
        }

        return null;
    }
}
