<?php

declare(strict_types=1);

namespace App\Domain\MFA;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\DefaultTwoFactorFormRenderer;
use Twig\Environment;

class TwoFactorFormRenderer extends DefaultTwoFactorFormRenderer
{
    /**
     * @param array<string,mixed> $templateVars
     */
    public function __construct(Environment $twigEnvironment, array $templateVars = [],
    ) {
        parent::__construct($twigEnvironment, 'v2/user/2fa_form.html.twig', $templateVars);
    }
}
