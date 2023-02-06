<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueExternalLink extends Constraint
{
    public string $message = 'The client "{{ string }}" can only be chosen once.';
    public string $messageMissing = 'Missing client.';
    public string $messageDuplicate = 'The client "{{ string }}" has already link.';

    public function validatedBy(): string
    {
        return 'reo_auth.validator.constraints.unique_external_link_validator';
    }
}
