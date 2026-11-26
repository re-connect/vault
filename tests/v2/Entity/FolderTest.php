<?php

namespace App\Tests\v2\Entity;

use App\Entity\Beneficiaire;
use App\Entity\Dossier;
use App\Entity\FolderIcon;
use App\Tests\Factory\BeneficiaireFactory;
use App\Validator\Constraints\Folder\NoCircularDependency;
use App\Validator\Constraints\Folder\SameBeneficiaryAsFolder;
use Zenstruck\Foundry\Test\Factories;

class FolderTest extends AbstractEntityTest
{
    use Factories;

    public function testEntityIsValid(): void
    {
        $this->assertEntityIsValid($this->getValidEntity());
    }

    /**
     * @param \Closure(self): Dossier $buildEntity
     *
     * @dataProvider provideInvalidEntities
     */
    public function testEntityIsNotValid(\Closure $buildEntity, string $property, string $constraintClass): void
    {
        $this->assertEntityIsNotValid($buildEntity($this), $property, $constraintClass);
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

    /**
     * Entities are built lazily inside the test: data providers run before setUp(),
     * outside the DAMA transaction and with a possibly stale Foundry/kernel state.
     */
    public static function provideInvalidEntities(): \Generator
    {
        yield 'Should fail when folder is child of itself' => [
            static function (self $test): Dossier {
                $folder = $test->getValidEntity();

                return $folder->setDossierParent($folder);
            },
            'dossierParent',
            NoCircularDependency::class,
        ];

        yield 'Should fail when folder is child of its child' => [
            static function (self $test): Dossier {
                [$folder, $childFolder] = $test->createFolderHierarchy();

                return $folder->setDossierParent($childFolder);
            },
            'dossierParent',
            NoCircularDependency::class,
        ];

        yield 'Should fail when folder is child of its grandchild' => [
            static function (self $test): Dossier {
                [$folder, , $grandChildFolder] = $test->createFolderHierarchy();

                return $folder->setDossierParent($grandChildFolder);
            },
            'dossierParent',
            NoCircularDependency::class,
        ];

        yield 'Should fail when parent folder belongs to another beneficiary' => [
            static function (self $test): Dossier {
                $beneficiaries = BeneficiaireFactory::randomSet(2);
                $parentFolder = $test->getValidEntity($beneficiaries[0]);

                return $test->getValidEntity($beneficiaries[1])->setDossierParent($parentFolder);
            },
            'dossierParent',
            SameBeneficiaryAsFolder::class,
        ];
    }

    /**
     * @return array{Dossier, Dossier, Dossier} folder, child, grandchild
     */
    public function createFolderHierarchy(): array
    {
        // A whole hierarchy belongs to a single beneficiary
        $beneficiary = BeneficiaireFactory::random();
        $folder = $this->getValidEntity($beneficiary);
        $childFolder = $this->getValidEntity($beneficiary);
        $grandChildFolder = $this->getValidEntity($beneficiary);

        $folder->addSousDossier($childFolder);
        $childFolder->addSousDossier($grandChildFolder);

        return [$folder, $childFolder, $grandChildFolder];
    }

    public function getValidEntity(?Beneficiaire $beneficiary = null): Dossier
    {
        $beneficiary ??= BeneficiaireFactory::random();

        return (new Dossier())->setNom('folder_test')->setBeneficiaire($beneficiary);
    }
}
