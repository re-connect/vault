<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Beneficiary requirements : 9 chars, 1 uppercase, 1 lowercase, 1 number.
 * Pro requirements: 10 chars, and 3 of 4 criteria : 1 uppercase, 1 lowercase, 1 number, 1 special.
 */
class PasswordCriteriaValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        $isBeneficiary = $constraint->isBeneficiary ?? $this->context->getObject()->isBeneficiaire();

        $this->validatePasswordLength($value, $isBeneficiary);
        $this->validatePasswordFormat($value);
        $this->validatePasswordCriteria($value, $isBeneficiary);
    }

    private function validatePasswordLength(string $value, bool $isBeneficiary): void
    {
        $passwordMinLength = $isBeneficiary ? User::BENEFICIARY_PASSWORD_LENGTH : User::PRO_PASSWORD_LENGTH;

        if (strlen($value) < $passwordMinLength) {
            $this->context->buildViolation('password_too_short', ['{{ limit }}' => $passwordMinLength])
                ->setTranslationDomain('messages')
                ->atPath('plainPassword')
                ->addViolation();
        }
    }

    private function validatePasswordFormat(string $value): void
    {
        if (!preg_match('#^[\S]+$#', $value)) {
            $this->context->buildViolation('password_wrong_format')
                ->setTranslationDomain('messages')
                ->atPath('plainPassword')
                ->addViolation();
        }
    }

    public function validatePasswordCriteria(string $value, bool $isBeneficiary): void
    {
        $violations = [];
        $authorizedViolations = $isBeneficiary ? 0 : 1;
        $criteria = [
            'number' => preg_match('/\d/', $value),
            'lowercase' => preg_match('/[a-z]/', $value),
            'uppercase' => preg_match('/[A-Z]/', $value),
        ];

        if (!$isBeneficiary) {
            $criteria['special'] = preg_match('/(?=.*\W)/', $value);
        }

        foreach ($criteria as $key => $criterion) {
            if (!$criterion) {
                $violations[$key] = $this->context->buildViolation('password_criterion_'.$key)
                    ->setTranslationDomain('messages')
                    ->atPath('plainPassword');
            }
        }

        $nbViolations = count($violations);

        if ($nbViolations > $authorizedViolations) {
            $this->context->buildViolation($isBeneficiary
                ? 'password_help_criteria_beneficiary'
                : 'password_help_criteria_pro',
                ['{{ atLeast }}' => $nbViolations - 1, '{{ total }}' => $nbViolations],
            )
            ->setTranslationDomain('messages')
            ->atPath('plainPassword')
            ->addViolation();

            foreach ($violations as $violation) {
                $violation->addViolation();
            }
        }
    }
}
