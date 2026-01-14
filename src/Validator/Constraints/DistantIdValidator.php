<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DistantIdValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (null !== $value && !preg_match("#^\d+$#", (string) $value, $matches)) {
            $this->context->buildViolation($constraint->message, ['%string%' => $value])->atPath('distantId')->addViolation();
        }
    }
}
