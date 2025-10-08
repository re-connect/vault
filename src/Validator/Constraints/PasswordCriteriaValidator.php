<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Password requirements : 9 chars, 1 uppercase, 1 lowercase, 1 special|number.
 */
class PasswordCriteriaValidator extends ConstraintValidator
{
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        $this->validatePasswordLength($value, $constraint);
        $this->validatePasswordFormat($value);
        $this->validatePasswordCriteria($value);
    }

    private function validatePasswordLength(string $value, Constraint $constraint): void
    {
        $user = $constraint->user ?? ($this->context->getObject() instanceof User ? $this->context->getObject() : null);
        $passwordMinLength = $user?->isAdmin() || $user?->isSuperAdmin()
            ? User::ADMIN_PASSWORD_LENGTH
            : User::USER_PASSWORD_LENGTH;

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

    public function validatePasswordCriteria(string $value): void
    {
        $violations = [];
        $criteria = [
            'lowercase' => preg_match('/[a-z]/', $value),
            'uppercase' => preg_match('/[A-Z]/', $value),
            'nonAlphabetic' => preg_match('/(?=.*\W|\d)/', $value),
        ];

        foreach ($criteria as $key => $criterion) {
            if (!$criterion) {
                $violations[$key] = $this->context->buildViolation('password_criterion_'.$key)
                    ->setTranslationDomain('messages')
                    ->atPath('plainPassword');
            }
        }

        if (count($violations) > 0) {
            $this->context->buildViolation('password_help_criteria')
            ->setTranslationDomain('messages')
            ->atPath('plainPassword')
            ->addViolation();

            foreach ($violations as $violation) {
                $violation->addViolation();
            }
        }
    }
}
