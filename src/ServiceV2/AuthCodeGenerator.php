<?php

namespace App\ServiceV2;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Erkens\Security\TwoFactorTextBundle\Generator\CodeGeneratorInterface as SMSCodeGenerator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface as EmailCodeGenerator;

readonly class AuthCodeGenerator
{
    public function __construct(private EmailCodeGenerator $emailCodeGenerator, private SMSCodeGenerator $smsCodeGenerator, private EntityManagerInterface $em)
    {
    }

    public function generateAndSendToUser(User $user): void
    {
        User::MFA_METHOD_EMAIL === $user->getMfaMethod() ? $this->emailCodeGenerator->generateAndSend($user) : $this->smsCodeGenerator->generateAndSend($user);
        $user->increaseMfaRetryCount();
        $this->em->flush();
    }
}
