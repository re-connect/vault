<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Username extends Constraint
{
    public $message = 'Ce champ ne peut contenir que des lettres des points, des chiffres ou des tirets.';
}
