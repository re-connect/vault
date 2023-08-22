<?php

namespace App\Validator\Constraints;

use App\Entity\Beneficiaire;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

class SecretAnswer extends Constraint
{
    #[HasNamedArguments]
    public function __construct(public Beneficiaire $beneficiary, mixed $options = null, array $groups = null, mixed $payload = null)
    {
        parent::__construct($options, $groups, $payload);
    }

    public $message = 'wrong_secret_answer';
}
