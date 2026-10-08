<?php

namespace App\Tests\v2\API\v2\PasswordGrant\Folder;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Dossier;
use App\Entity\FolderIcon;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\API\v2\PasswordGrant\AbstractPasswordGrantApiV2TestCase;
use Symfony\Component\HttpFoundation\Request;

class MobileFolderTest extends AbstractPasswordGrantApiV2TestCase
{
    public function testCreateFolderWithMobilePayload(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $parentFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);
        $icon = $this->createFolderIcon();

        $response = $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_POST, sprintf('/beneficiaries/%d/folders', $beneficiary->getId()), ['json' => [
            'nom' => 'Mon dossier',
            'dossier_parent_id' => $parentFolder->getId(),
            'icon_id' => $icon->getId(),
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $folder = $this->findFolder($response->toArray()['id']);
        $this->assertSame('Mon dossier', $folder->getNom());
        $this->assertSame($parentFolder->getId(), $folder->getDossierParent()?->getId());
        $this->assertSame($icon->getId(), $folder->getIcon()?->getId());
    }

    public function testRenameFolderAndChangeIcon(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $folder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);
        $icon = $this->createFolderIcon();

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_PATCH, sprintf('/folders/%d', $folder->getId()), ['json' => [
            'name' => 'Nouveau nom',
            'nom' => 'Nouveau nom',
            'icon_id' => $icon->getId(),
        ]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['id' => $folder->getId(), 'nom' => 'Nouveau nom']);
        $this->assertSame($icon->getId(), $this->findFolder($folder->getId())->getIcon()?->getId());
    }

    public function testToggleFolderAccess(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $folder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_PATCH, sprintf('/folders/%d/toggle-access', $folder->getId()));

        $this->assertResponseStatusCodeSame(202);
        $this->assertTrue($this->findFolder($folder->getId())->getBPrive());
    }

    public function testDeleteFolder(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $folder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false]);
        $folderId = $folder->getId();

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_DELETE, sprintf('/folders/%d', $folderId));

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull($this->findFolder($folderId));
    }

    private function createFolderIcon(): FolderIcon
    {
        $icon = (new FolderIcon())->setName('Santé')->setFileName('health.svg');
        $this->em->persist($icon);
        $this->em->flush();

        return $icon;
    }

    private function findFolder(int $id): ?Dossier
    {
        $this->em->clear();

        return $this->em->find(Dossier::class, $id);
    }
}
