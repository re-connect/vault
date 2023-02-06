<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UsernameValidator.
 */
class UsernameValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint)
    {
        /* @var Username $constraint */
        if (null !== $value && !preg_match('#^[/0-9a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð .-]+$#', $value, $matches)) {
            $this->context->addViolation($constraint->message, ['%string%' => $value]);
        }
    }
}
