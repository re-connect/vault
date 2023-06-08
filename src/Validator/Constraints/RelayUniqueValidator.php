<?php

namespace App\Validator\Constraints;

use App\Entity\MembreCentre;
use Doctrine\Common\Collections\ReadableCollection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RelayUniqueValidator extends ConstraintValidator
{
    /**
     * @param ReadableCollection<MembreCentre> $value
     */
    public function validate(mixed $value, Constraint $constraint)
    {
        $duplicates = $this->getDuplicates($value);
        foreach ($duplicates as $duplicate) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ relay }}', $duplicate)
                ->addViolation();
        }
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
