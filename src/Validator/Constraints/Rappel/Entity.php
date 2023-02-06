<?php

namespace App\Validator\Constraints\Rappel;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Entity extends Constraint
{
    public $message = '';
    public $messageRappelBeforeNow = 'La date de rappel est déjà passée.';
    public $messageSMSAlreadySend = 'La date du rappel "{{ string }}" n\'est pas modifiable car un SMS a déjà été envoyé.';
    public $messageRappelAfterDateEvent = 'La date de rappel doit se situer avant la date de l\'événement.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
