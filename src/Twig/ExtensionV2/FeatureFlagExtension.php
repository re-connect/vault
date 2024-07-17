<?php

namespace App\Twig\ExtensionV2;

use App\Checker\FeatureFlagChecker;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FeatureFlagExtension extends AbstractExtension
{
    public function __construct(private readonly FeatureFlagChecker $featureFlagChecker)
    {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_feature_enabled', $this->featureFlagChecker->isEnabled(...)),
        ];
    }
}
