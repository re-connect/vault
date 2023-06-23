<?php

namespace App\FormV2\ResetPassword\BeneficiaryRequest;

use App\Entity\Beneficiaire;
use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordSecretAnswerFormModel
{
    #[Assert\Expression(
        'this.beneficiary.getReponseSecreteToLowerCase() == this.getSecretAnswerToLowerCase()',
        message: 'secret_answer_mismatch'
    )]
    public ?string $secretAnswer = '';

    public function __construct(public readonly Beneficiaire $beneficiary)
    {
    }

    public function getSecretAnswerToLowerCase(): string
    {
        return strtolower($this->secretAnswer);
    }
}
