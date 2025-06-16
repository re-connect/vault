<?php

namespace App\DataFixtures\ORM;

use App\Entity\Attributes\TypeCentre;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class TypeCentreFixture extends Fixture implements FixtureGroupInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $arTypes = ['CHRS', 'CHU', 'LHSS', 'CADA', 'CAARUD', 'CSAPA', 'ACT', 'Résidence Sociale', 'PSA', 'Autre'];

        foreach ($arTypes as $typeCentreStr) {
            $typeCentre = new TypeCentre();
            $typeCentre->setNom($typeCentreStr);
            $manager->persist($typeCentre);
        }
        $manager->flush();
    }

    /** @return string[] */
    #[\Override]
    public static function getGroups(): array
    {
        return ['v1'];
    }
}
