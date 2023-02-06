<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class RappelUserDontHavePhone extends Constraint
{
    public $message = 'L\'utilisateur ne peut recevoir des SMS car il n\'a renseigné aucun téléphone';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
