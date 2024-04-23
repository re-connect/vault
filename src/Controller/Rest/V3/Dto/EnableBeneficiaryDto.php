<?php

namespace App\Controller\Rest\V3\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class EnableBeneficiaryDto
{
    public function __construct(
        public ?string $secretQuestion,
        public ?string $otherSecretQuestion,
        #[Assert\NotBlank]
        public ?string $secretAnswer,
        #[Assert\NotBlank]
        public ?string $password,
        #[Assert\Email]
        public ?string $email,
    ) {
    }
}
