<?php

namespace App\EventSubscriber;

use App\Domain\MFA\ExpiredTwoFactorCodeException;
use App\ServiceV2\Traits\SessionsAwareTrait;
use App\ServiceV2\Traits\UserAwareTrait;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsEventListener(TwoFactorAuthenticationEvents::ATTEMPT, 'checkCodeExpiration')]
class ExpireMfaTokenSubscriber
{
    use UserAwareTrait;
    use SessionsAwareTrait;

    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack)
    {
    }

    /** @throws ExpiredTwoFactorCodeException */
    public function checkCodeExpiration(TwoFactorAuthenticationEvent $event): void
    {
        if ($this->getUser()?->isMfaCodeExpired()) {
            throw new ExpiredTwoFactorCodeException();
        }
    }
}
