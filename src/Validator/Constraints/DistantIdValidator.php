<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DistantIdValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (null !== $value && !preg_match("#^\d+$#", $value, $matches)) {
            $this->context->buildViolation($constraint->message, ['%string%' => $value])->atPath('distantId')->addViolation();
        }
    }
}
