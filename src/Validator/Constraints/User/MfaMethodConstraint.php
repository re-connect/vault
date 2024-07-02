<?php

namespace App\Validator\Constraints\User;

use Symfony\Component\Validator\Constraint;

#[Attribute]
class MfaMethodConstraint extends Constraint
{
    public string $noEmailAddress = 'no_email_address';
    public string $noPhoneNumber = 'no_phone_number';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
