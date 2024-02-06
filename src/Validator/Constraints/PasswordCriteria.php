<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PasswordCriteria extends Constraint
{
    public ?bool $isBeneficiary = null;
}
