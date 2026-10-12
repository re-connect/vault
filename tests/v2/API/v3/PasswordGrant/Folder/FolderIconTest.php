<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Folder;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Dossier;
use App\Entity\FolderIcon;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class FolderIconTest extends AbstractPasswordGrantApiTestCase
{
    public function testListFolderIconsWithTranslatedNames(): void
    {
        $icon = $this->createIcon('health');

        $response = $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_GET, '/folder_icons', [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertContains(
            ['id' => $icon->getId(), 'name' => 'Santé', 'url' => $icon->getPublicFilePath()],
            $response->toArray(),
        );
    }

    public function testListingFolderIconsRequiresAuthentication(): void
    {
        static::createClient()->request(Request::METHOD_GET, $this->generateUrl('/folder_icons'));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateFolderWithIcon(): void
    {
        $icon = $this->createIcon('health');

        $response = $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_POST, '/folders', [
            'json' => ['nom' => 'Santé', 'icon' => $this->getIconIri($icon)],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['icon' => $this->getIconIri($icon), 'icon_file_path' => $icon->getPublicFilePath()]);
        $this->assertSame($icon->getId(), $this->findFolder($response->toArray()['id'])->getIcon()?->getId());
    }

    public function testChangeAndRemoveFolderIcon(): void
    {
        $folder = FolderFactory::createOne(['beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL), 'bPrive' => false]);
        $icon = $this->createIcon('health');

        $this->patchFolder($folder, ['icon' => $this->getIconIri($icon)]);
        $this->assertResponseIsSuccessful();
        $this->assertSame($icon->getId(), $this->findFolder($folder->getId())->getIcon()?->getId());

        $this->patchFolder($folder, ['icon' => null]);
        $this->assertResponseIsSuccessful();
        $this->assertNull($this->findFolder($folder->getId())->getIcon());
    }

    public function testCanNotSetUnknownIcon(): void
    {
        $folder = FolderFactory::createOne(['beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL), 'bPrive' => false]);

        $this->patchFolder($folder, ['icon' => '/api/v3/folder_icons/999999999']);

        $this->assertResponseStatusCodeSame(400);
    }

    private function createIcon(string $name): FolderIcon
    {
        $icon = (new FolderIcon())->setName($name)->setFileName(sprintf('%s.svg', $name));
        $this->em->persist($icon);
        $this->em->flush();

        return $icon;
    }

    private function getIconIri(FolderIcon $icon): string
    {
        return sprintf('/api/v3/folder_icons/%d', $icon->getId());
    }

    /** @param array<string, mixed> $body */
    private function patchFolder(Dossier $folder, array $body): void
    {
        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_PATCH, sprintf('/folders/%d', $folder->getId()), [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $body,
        ]);
    }

    private function findFolder(int $id): Dossier
    {
        $this->em->clear();

        return $this->em->find(Dossier::class, $id);
    }
}
