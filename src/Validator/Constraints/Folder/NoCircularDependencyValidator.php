<?php

namespace App\Validator\Constraints\Folder;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

class NoCircularDependencyValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoCircularDependency) {
            throw new UnexpectedTypeException($constraint, NoCircularDependency::class);
        }

        $folder = $this->context->getObject();
        $parentFolder = $folder->getDossierParent();

        if (!$parentFolder) {
            return;
        }

        if ($folder === $parentFolder || $folder->isParentFolderInHierarchy($parentFolder)) {
            $this->context->buildViolation($this->translator->trans('folder_circular_dependency'))->addViolation();
        }
    }
}
