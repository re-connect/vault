<?php

namespace App\Domain\MFA;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Erkens\Security\TwoFactorTextBundle\Generator\CodeGeneratorInterface as SMSCodeGenerator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;

readonly class MfaCodeSender
{
    public function __construct(
        private EntityManagerInterface $em,
        private CodeGeneratorInterface $emailCodeGenerator,
        private SMSCodeGenerator $smsCodeGenerator,
    ) {
    }

    public function checkCode(User $user, mixed $authCode): void
    {
        if (!$user->isMfaEnabled()) {
            return;
        }

        if (!$authCode) {
            $this->sendCode($user);
            $user->setMfaPending(true);
        } else {
            $user->setMfaValid($authCode === $user->getEmailAuthCode());
        }

        $this->em->flush();
    }

    public function sendCode(User $user): void
    {
        if ($user->isMfaCodeCountLimitReach()) {
            if ($user->isMfaCodeExpired()) {
                $user->resetMfaRetryCount();
            } else {
                return;
            }
        }

        User::MFA_METHOD_EMAIL === $user->getMfaMethod()
            ? $this->emailCodeGenerator->generateAndSend($user)
            : $this->smsCodeGenerator->generateAndSend($user);
        $user->increaseMfaRetryCount();
        $this->em->flush();
    }
}
