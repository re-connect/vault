<?php

namespace App\Validator\Constraints\Evenement;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Entity extends Constraint
{
    public $message = '';
    public $messageRappelBeforeNow = 'La date de l\'événement est déjà passée.';
    public $messageRappelAfterDateEvent = 'La date de rappel doit se situer avant la date de l\'événement.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
