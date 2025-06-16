<?php

namespace App\Validator\Constraints\Rappel;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute] class Entity extends Constraint
{
    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
