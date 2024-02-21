<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AcceptCGSValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof AcceptCGS) {
            throw new UnexpectedTypeException($constraint, AcceptCGSValidator::class);
        }

        if (!$value) {
            $this->context->buildViolation($constraint->message)->atPath('accept')->addViolation();
        }
    }
}
