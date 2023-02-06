<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DistantId extends Constraint
{
    public $message = 'L\'identifiant distant doit être un nombre.';
}
