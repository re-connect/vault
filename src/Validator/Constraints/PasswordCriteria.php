<?php

namespace App\Validator\Constraints;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PasswordCriteria extends Constraint
{
    public function __construct(public ?UserInterface $user = null, mixed $options = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct($options, $groups, $payload);
    }
}
