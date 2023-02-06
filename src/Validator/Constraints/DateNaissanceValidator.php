<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DateNaissanceValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (null !== $value && !preg_match("#^\d{2}/\d{2}/\d{4}$#", $value, $matches)) {
            $this->context->buildViolation($constraint->message, ['%string%' => $value])->atPath('dateNaissance')->addViolation();
        }
    }
}
