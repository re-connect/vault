<?php

namespace App\Validator\Constraints;

use App\Entity\Evenement;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SpecifierRappelValidator extends ConstraintValidator
{
    /**
     * @param Evenement $value
     */
    public function validate($value, Constraint $constraint)
    {
//        throw new \Exception(__METHOD__);
//        if (!$constraint instanceof Evenement) {
//            throw new UnexpectedTypeException($constraint, SpecifierRappel::class);
//        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

//        if ($value->getRappels()->count() === 0) {
//            $this->context->addViolation($constraint->message, array('%string%' => $value));
//            return;
//        }

        foreach ($value->getRappels() as $rappel) {
            if ($rappel->getDate() > $value->getDate()) {
                $this->context->addViolation($constraint->messageRappelAfterDateEvent);

                return;
            }
        }
    }
}
