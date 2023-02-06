<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DateNaissance extends Constraint
{
    public $message = 'La date de naissance doit être au format dd/mm/YYYY.';
}
