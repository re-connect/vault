<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class SpecifierRappel extends Constraint
{
//    public $message = 'evenement.errorSpecifierHeureRappel';
    public $message = 'Vous devez renseigner un rappel.';
    public $messageRappelAfterDateEvent = 'La date de rappel doit se situer avant la date de l\'événement.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
