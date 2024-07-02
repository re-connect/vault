<?php

declare(strict_types=1);

namespace App\Domain\MFA;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @final
 */
class ExpiredTwoFactorCodeException extends BadCredentialsException
{
    public const string MESSAGE = 'Expired two-factor authentication code.';
    private const string MESSAGE_KEY = 'mfa_code_expired';

    #[\Override]
    public function getMessageKey(): string
    {
        return self::MESSAGE_KEY;
    }
}
