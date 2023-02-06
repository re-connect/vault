<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Name extends Constraint
{
    public $message = 'Ce champ ne peut contenir que des lettres et des tirets.';
}
