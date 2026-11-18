<?php

namespace App\Validator\Constraints\Folder;

use App\Entity\DonneePersonnelle;
use App\Entity\Dossier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SameBeneficiaryAsFolderValidator extends ConstraintValidator
{
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SameBeneficiaryAsFolder) {
            throw new UnexpectedTypeException($constraint, SameBeneficiaryAsFolder::class);
        }

        if (!$value instanceof Dossier) {
            return;
        }

        $item = $this->context->getObject();
        if ($item instanceof DonneePersonnelle && $value->getBeneficiaire() !== $item->getBeneficiaire()) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
