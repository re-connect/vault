<?php

namespace App\Tests\v2\Entity;

use App\Entity\Dossier;
use App\Entity\FolderIcon;
use App\Tests\Factory\BeneficiaireFactory;
use App\Validator\Constraints\Folder\NoCircularDependency;
use Zenstruck\Foundry\Test\Factories;

class FolderTest extends AbstractEntityTest
{
    use Factories;

    public function testEntityIsValid(): void
    {
        $this->assertEntityIsValid($this->getValidEntity());
    }

    /** @dataProvider provideInvalidEntities */
    public function testEntityIsNotValid(Dossier $entity, string $property, string $constraintClass): void
    {
        $this->assertEntityIsNotValid($entity, $property, $constraintClass);
    }

    public function testRemoveIcon(): void
    {
        $folder = $this->getValidEntity();
        $icon = (new FolderIcon())->setName('dummy')->setFileName('dummy');

        self::assertNull($folder->getIcon());

        $folder->setIcon($icon);
        $this->em->persist($icon);
        $this->em->persist($folder);
        $this->em->flush();

        $this->em->refresh($folder);
        self::assertNotNull($folder->getIcon());
        self::assertSame($folder->getIcon(), $icon);

        $this->em->remove($icon);
        $this->em->flush();

        $this->em->refresh($folder);
        self::assertNull($folder->getIcon());
    }

    public function provideInvalidEntities(): \Generator
    {
        $this->setUp();
        $folder = $this->getValidEntity();
        $folder->setDossierParent($folder);
        yield 'Should fail when folder is child of itself' => [$folder, 'dossierParent', NoCircularDependency::class];

        $folder = $this->getValidEntity();
        $childFolder = $this->getValidEntity();
        $grandChildfolder = $this->getValidEntity();

        $folder->addSousDossier($childFolder);
        $childFolder->addSousDossier($grandChildfolder);

        $this->em->persist($folder);
        $this->em->persist($childFolder);
        $this->em->persist($grandChildfolder);
        $this->em->flush();

        $folder->setDossierParent($childFolder);
        yield 'Should fail when folder is child of its child' => [$folder, 'dossierParent', NoCircularDependency::class];

        $folder->setDossierParent($grandChildfolder);
        yield 'Should fail when folder is child of its grandchild' => [$folder, 'dossierParent', NoCircularDependency::class];
    }

    public function getValidEntity(): Dossier
    {
        $beneficiary = BeneficiaireFactory::random()->object();

        return (new Dossier())->setNom('folder_test')->setBeneficiaire($beneficiary);
    }
}
