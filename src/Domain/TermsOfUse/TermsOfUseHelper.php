<?php

namespace App\Domain\TermsOfUse;

use App\Checker\FeatureFlagChecker;
use App\Entity\User;

class TermsOfUseHelper
{
    public const string CGS_FEATURE_FLAG_NAME = 'new-cgs';

    public function __construct(private readonly FeatureFlagChecker $featureFlagChecker)
    {
    }

    public function mustAcceptTermsOfUse(User $user): bool
    {
        if ($this->featureFlagChecker->isEnabled(self::CGS_FEATURE_FLAG_NAME)) {
            return !$user->isSuperAdmin() && !$user->isAdministrateur() && $this->mustAcceptNewTerms($user);
        }

        return $user->mustAcceptTermsOfUse();
    }

    private function mustAcceptNewTerms(User $user): bool
    {
        return !$user->getCgsAcceptedAt() || $user->getCgsAcceptedAt() < $this->featureFlagChecker->getEnableDate(self::CGS_FEATURE_FLAG_NAME);
    }
}
