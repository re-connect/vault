<?php

namespace App\Tests\v2\Validator;

use App\Entity\Centre;
use App\Entity\Membre;
use App\Entity\MembreCentre;
use App\Validator\Constraints\RelayUnique;
use App\Validator\Constraints\RelayUniqueValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class RelayUniqueConstraintTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): RelayUniqueValidator
    {
        return new RelayUniqueValidator();
    }

    public function testNoDuplicatesIsValid(): void
    {
        $this->validator->validate(new ArrayCollection([$this->getDummyMembreCentre()]), new RelayUnique());

        $this->assertNoViolation();
    }

    public function testDuplicatesIsNotValid(): void
    {
        $constraint = new RelayUnique();
        $membreCentre = $this->getDummyMembreCentre();

        $this->validator->validate(new ArrayCollection([$membreCentre, $membreCentre]), $constraint);

        $this->buildViolation($constraint->message)->setParameter('{{ relay }}', 'test')->assertRaised();
    }

    private function getDummyMembreCentre(): MembreCentre
    {
        return (new MembreCentre())->setCentre((new Centre())->setNom('test'))->setMembre(new Membre());
    }
}
