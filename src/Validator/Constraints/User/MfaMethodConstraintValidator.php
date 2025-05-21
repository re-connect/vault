<?php

namespace App\Validator\Constraints\User;

use App\Entity\Attributes\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

class MfaMethodConstraintValidator extends ConstraintValidator
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @param User $user
     * */
    #[\Override]
    public function validate(mixed $user, Constraint $constraint): void
    {
        if (!$user instanceof User) {
            throw new UnexpectedTypeException($user, User::class);
        }

        if (!$constraint instanceof MfaMethodConstraint) {
            throw new UnexpectedTypeException($constraint, MfaMethodConstraint::class);
        }

        if (!$user->isMfaEnabled()) {
            return;
        }

        if (User::MFA_METHOD_EMAIL === $user->getMfaMethod() && !$user->getEmail()) {
            $this->context->buildViolation($this->translator->trans($constraint->noEmailAddress))->atPath('mfaMethod')->addViolation();

            return;
        }
        if (User::MFA_METHOD_SMS === $user->getMfaMethod() && null === $user->getTelephone()) {
            $this->context->buildViolation($this->translator->trans($constraint->noPhoneNumber))->atPath('mfaMethod')->addViolation();
        }
    }
}
