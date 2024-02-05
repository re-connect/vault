<?php

namespace App\ServiceV2\Helper;

use App\Validator\Constraints\PasswordCriteria;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class PasswordHelper
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function isStrongPassword(string $password, bool $isBeneficiary): bool
    {
        return 0 === count($this->validator->validate($password, new PasswordCriteria(['isBeneficiary' => $isBeneficiary])));
    }
}
