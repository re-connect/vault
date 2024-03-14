<?php

namespace App\Tests\v2\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractEntityTest extends KernelTestCase
{
    protected ValidatorInterface $validator;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function assertEntityIsValid(object $entity): void
    {
        $violations = $this->validator->validate($entity);
        $this->assertCount(0, $violations);

        // This will throw an error if validation does not cover all db constraints
        $this->em->persist($entity);
        $this->em->flush();
        $this->addToAssertionCount(1);
    }

    public function assertEntityIsNotValid(object $entity, string $property, string $constraintClass): void
    {
        $violations = $this->validator->validate($entity);
        $this->assertCount(1, $violations);
        /** @var ConstraintViolation $violation */
        $violation = $violations[0];
        $this->assertEquals($property, $violation->getPropertyPath());
        $this->assertEquals($constraintClass, get_class($violation->getConstraint()));
    }
}
