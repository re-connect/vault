<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueExternalLink extends Constraint
{
    public string $message = 'The client "{{ string }}" can only be chosen once';
    public string $messageMissing = 'Missing client';
    public string $messageDuplicate = 'The client "{{ string }}" is already linked';
    public string $messageDistantIdDuplicate = 'You already have a beneficiary with the distant id "{{ string }}"';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
