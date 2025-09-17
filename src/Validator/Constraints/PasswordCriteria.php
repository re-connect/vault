<?php

namespace App\Validator\Constraints;

use App\Entity\Attributes\User;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PasswordCriteria extends Constraint
{
    public function __construct(public ?User $user = null, mixed $options = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct($options, $groups, $payload);
    }
}
