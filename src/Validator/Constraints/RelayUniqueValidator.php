<?php

namespace App\Validator\Constraints;

use App\Entity\Attributes\MembreCentre;
use Doctrine\Common\Collections\ReadableCollection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RelayUniqueValidator extends ConstraintValidator
{
    /**
     * @param ReadableCollection<MembreCentre> $value
     */
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        $duplicates = $this->getDuplicates($value);
        array_map(fn ($duplicate) => $this->context->buildViolation($constraint->message)
            ->setParameter('{{ relay }}', $duplicate)
            ->addViolation(),
            $duplicates,
        );
    }

    /**
     * @param ReadableCollection<MembreCentre> $membreCentres
     */
    private function getDuplicates(ReadableCollection $membreCentres): array
    {
        $duplicates = [];
        foreach ($membreCentres as $current) {
            $currentCentre = $current->getCentre();
            $countEntries = $membreCentres->filter(fn (MembreCentre $membreCentre) => $membreCentre->getCentre() === $currentCentre)->count();
            if ($countEntries > 1 && !in_array($currentCentre, $duplicates)) {
                $duplicates[] = $currentCentre;
            }
        }

        return $duplicates;
    }
}
