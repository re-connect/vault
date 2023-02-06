<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ContainsAlphanumeric extends Constraint
{
    public $message = 'Ce champ ne peut contenir que des caractères alphanumeriques.';
}
