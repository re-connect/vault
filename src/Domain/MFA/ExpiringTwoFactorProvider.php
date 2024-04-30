<?php

namespace App\Domain\MFA;

use App\Entity\User;
use Erkens\Security\TwoFactorTextBundle\Generator\CodeGeneratorInterface as TextCodeGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface as EmailCodeGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: 'scheb_two_factor.provider', attributes: ['alias' => 'scheb_two_factor.provider'])]
readonly class ExpiringTwoFactorProvider implements TwoFactorProviderInterface
{
    public function __construct(
        private TextCodeGeneratorInterface $textCodeGenerator,
        private EmailCodeGeneratorInterface $emailCodeGenerator,
        private TwoFactorFormRenderer $formRenderer,
    ) {
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $user->isMfaEnabled();
    }

    public function prepareAuthentication(object $user): void
    {
        if ($user instanceof User) {
            $this->generateAndSendCode($user);
        }
    }

    public function validateAuthenticationCode(object $user, string $authenticationCode): bool
    {
        if (!($user instanceof User)) {
            return false;
        }
        // Strip any user added spaces
        $authenticationCode = str_replace(' ', '', $authenticationCode);

        if ($user->getAuthCode() !== $authenticationCode) {
            return false;
        }

        if ($user->isMfaCodeExpired()) {
            throw new ExpiredTwoFactorCodeException();
        }

        return true;
    }

    public function getFormRenderer(): TwoFactorFormRenderer
    {
        return $this->formRenderer;
    }

    public function resendAuthenticationCode($user): void
    {
        if ($user instanceof User) {
            $this->getCodeGenerator($user)->reSend($user);
        }
    }

    private function getCodeGenerator(User $user): EmailCodeGeneratorInterface|TextCodeGeneratorInterface
    {
        return User::MFA_METHOD_EMAIL === $user->getMfaMethod()
            ? $this->emailCodeGenerator
            : $this->textCodeGenerator;
    }

    public function generateAndSendCode(User $user): void
    {
        if (!$user->isMfaCodeCountLimitReach()) {
            $user->increaseMfaRetryCount();
            $this->getCodeGenerator($user)->generateAndSend($user);
        }
    }
}
