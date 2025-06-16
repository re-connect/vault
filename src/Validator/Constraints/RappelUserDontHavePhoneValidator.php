<?php

namespace App\Validator\Constraints;

use App\Entity\Attributes\Evenement;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RappelUserDontHavePhoneValidator extends ConstraintValidator
{
    /**
     * @param Evenement $value
     */
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if (null === $value->getBeneficiaire()->getUser()->getTelephone() && in_array(Evenement::EVENEMENT_RAPPEL_SMS, $value->getRappels())) {
            $this->context->addViolation($constraint->message);
        }
    }
}
