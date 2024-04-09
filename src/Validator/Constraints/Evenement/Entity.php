<?php

namespace App\Validator\Constraints\Evenement;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Entity extends Constraint
{
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
