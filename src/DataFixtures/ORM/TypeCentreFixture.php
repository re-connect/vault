<?php

namespace App\DataFixtures\ORM;

use App\Entity\TypeCentre;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class TypeCentreFixture extends Fixture implements FixtureGroupInterface
{
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
    public static function getGroups(): array
    {
        return ['v1'];
    }
}
