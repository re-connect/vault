<?php

namespace App\Tests\v2\Entity;

use App\Entity\Attributes\Centre;
use App\Entity\Region;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegionTest extends AbstractEntityTest
{
    /** @dataProvider provideValidEntities */
    public function testEntityIsValid(Region $region): void
    {
        $this->assertEntityIsValid($region);
    }

    public function provideValidEntities(): \Generator
    {
        yield 'Region without email is valid' => [(new Region())->setName('Bretagne')];
        yield 'Region with email is valid' => [(new Region())->setName('Normandie')->setEmail('normandie@reconnect.fr')];
    }

    /** @dataProvider provideInvalidEntities */
    public function testEntityIsNotValid(Region $region, string $property, string $constraintClass): void
    {
        $this->assertEntityIsNotValid($region, $property, $constraintClass);
    }

    public function provideInvalidEntities(): \Generator
    {
        yield 'Region with existing name should be invalid' => [(new Region())->setName('Occitanie'), 'name', UniqueEntity::class];
        yield 'Region with email in wrong format should be invalid' => [(new Region())->setName('Bretagne')->setEmail('wrong_format_string'), 'email', Email::class];
        yield 'Region without name should be invalid' => [new Region(), 'name', NotBlank::class];
        yield 'Region with blank name should be invalid' => [(new Region())->setName(''), 'name', NotBlank::class];
    }

    public function testRemoveRegionDoesNotRemoveCentres(): void
    {
        // Create and persist a Region with a Centre attached
        $centresRepo = $this->em->getRepository(Centre::class);
        $centre = $centresRepo->findAll()[0];
        $centreId = $centre->getId();
        $this->assertNotNull($centre);
        $region = (new Region())->setName('MaRegion')->addCentre($centre);
        $this->assertNull($region->getId());
        $this->em->persist($region);
        $this->em->flush();
        $this->em->refresh($region);
        $this->assertNotNull($region->getId());

        // Remove the Region from db
        $this->em->remove($region);
        $this->em->flush();

        // Assert that the Centre still exists
        $this->assertNotNull($centresRepo->find($centreId));
    }
}
