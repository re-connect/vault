<?php

namespace App\Tests\v2\Entity;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Tests\Factory\BeneficiaireFactory;
use App\Validator\Constraints\Folder\SameBeneficiaryAsFolder;
use Zenstruck\Foundry\Test\Factories;

class DocumentConstraintsTest extends AbstractEntityTest
{
    use Factories;

    public function testDocumentInFolderOfSameBeneficiaryIsValid(): void
    {
        $beneficiary = BeneficiaireFactory::random();
        $folder = (new Dossier())->setNom('folder_test')->setBeneficiaire($beneficiary);
        $document = (new Document())->setNom('document_test')->setBeneficiaire($beneficiary)->setDossier($folder);

        self::assertCount(0, $this->validator->validate($document));
    }

    public function testDocumentInFolderOfAnotherBeneficiaryIsNotValid(): void
    {
        $beneficiaries = BeneficiaireFactory::randomSet(2);
        $folder = (new Dossier())->setNom('folder_test')->setBeneficiaire($beneficiaries[0]);
        $document = (new Document())->setNom('document_test')->setBeneficiaire($beneficiaries[1])->setDossier($folder);

        $this->assertEntityIsNotValid($document, 'dossier', SameBeneficiaryAsFolder::class);
    }
}
