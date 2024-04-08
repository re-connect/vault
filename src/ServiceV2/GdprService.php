<?php

namespace App\ServiceV2;

use App\Entity\User;
use App\ServiceV2\Traits\SessionsAwareTrait;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class GdprService
{
    use SessionsAwareTrait;
    use UserAwareTrait;

    public const RENEWAL_DAYS_COUNT = 7;
    public const EXPIRATION_DAYS_COUNT = 0;

    private TranslatorInterface $translator;

    public function __construct(RequestStack $requestStack, Security $security, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->security = $security;
        $this->translator = $translator;
    }

    public function isPasswordRenewalDue(): bool
    {
        return $this->getDaysBeforeExpiration() <= self::RENEWAL_DAYS_COUNT;
    }

    public function isPasswordExpired(?User $user = null): bool
    {
        return $this->getDaysBeforeExpiration($user) <= self::EXPIRATION_DAYS_COUNT;
    }

    private function getDaysBeforeExpiration(?User $user = null): int
    {
        $user = $user ?: $this->getUser();
        $now = new \DateTimeImmutable();

        return max((int) $now
            ->sub(new \DateInterval('P1Y'))
            ->diff($user->getPasswordUpdatedAt() ?? $now)
            ->format('%r%a'), 0);
    }

    public function showPasswordRenewalFlash(): void
    {
        if ($this->isPasswordRenewalDue()) {
            $daysBeforeExpiration = $this->getDaysBeforeExpiration();
            $message = $daysBeforeExpiration <= 0
                ? $this->translator->trans('password_expired')
                : $this->translator->trans('password_renewal_due', ['%daysRemaining%' => $daysBeforeExpiration]);
            $this->clearAndAddFlashMessage('danger', $message);
        }
    }
}
