<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class AcceptCGS extends Constraint
{
    public string $message = 'you_must_accept_terms_of_use';
}
