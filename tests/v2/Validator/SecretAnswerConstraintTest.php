<?php

namespace App\Tests\v2\Validator;

use App\Entity\Beneficiaire;
use App\Validator\Constraints\SecretAnswer;
use App\Validator\Constraints\SecretAnswerValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SecretAnswerConstraintTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): SecretAnswerValidator
    {
        return new SecretAnswerValidator();
    }

    public function testRightAnswerIsValid()
    {
        $this->validator->validate('answer', new SecretAnswer($this->getDummyBeneficiary()));

        $this->assertNoViolation();
    }

    public function testWrongAnswerIsNotValid()
    {
        $constraint = new SecretAnswer($this->getDummyBeneficiary());

        $this->validator->validate('wrong', $constraint);

        $this->buildViolation($constraint->message)->assertRaised();
    }

    private function getDummyBeneficiary(): Beneficiaire
    {
        return (new Beneficiaire())->setReponseSecrete('answer');
    }
}
