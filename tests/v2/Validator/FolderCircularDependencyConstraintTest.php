<?php

namespace App\Tests\v2\Validator;

use App\Entity\Dossier;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FolderCircularDependencyConstraintTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testCanNotValidateFolder(): void
    {
        $firstFolder = (new Dossier())->setNom('firstFolder');
        $secondFolder = (new Dossier())->setNom('secondFolder');

        $firstFolder->setDossierParent($firstFolder);
        $this->assertNotValid($firstFolder);

        $firstFolder->addSousDossier($firstFolder);
        $this->assertNotValid($firstFolder);

        $firstFolder->addSousDossier($secondFolder);
        $secondFolder->addSousDossier($firstFolder);
        $this->assertNotValid($firstFolder);
        $this->assertNotValid($secondFolder);

        $firstFolder->setDossierParent($secondFolder);
        $secondFolder->setDossierParent($firstFolder);
        $this->assertNotValid($firstFolder);
        $this->assertNotValid($secondFolder);
    }

    public function testCanValidateFolder(): void
    {
        $firstFolder = (new Dossier())->setNom('firstFolder');
        $secondFolder = (new Dossier())->setNom('secondFolder');

        $firstFolder->setDossierParent($secondFolder);
        $this->assertValid($firstFolder);

        $secondFolder->addSousDossier($firstFolder);
        $this->assertValid($secondFolder);
    }

    private function assertValid(Dossier $folder): void
    {
        $this->assertEmpty($this->validator->validate($folder));
    }

    private function assertNotValid(Dossier $folder): void
    {
        $this->assertNotEmpty($this->validator->validate($folder));
    }
}
