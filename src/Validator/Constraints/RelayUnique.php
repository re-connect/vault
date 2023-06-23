<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class RelayUnique extends Constraint
{
    public $message = 'Le membre est déjà lié au centre {{ relay }}';
}
