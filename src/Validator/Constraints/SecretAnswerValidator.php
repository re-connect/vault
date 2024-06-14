<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SecretAnswerValidator extends ConstraintValidator
{
    public function __construct()
    {
    }

    public function validate($value, Constraint $constraint)
    {
        if (strtolower((string) $constraint->beneficiary->getReponseSecrete()) !== strtolower((string) $value)) {
            $this->context->addViolation($constraint->message);
        }
    }
}
