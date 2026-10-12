<?php

namespace App\Tests\v2\API\v3\PasswordGrant\PersonalData;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Contact;
use App\Entity\DonneePersonnelle;
use App\Entity\Dossier;
use App\Entity\Evenement;
use App\Entity\Note;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class CreateTest extends AbstractPasswordGrantApiTestCase
{
    public function provideResources(): \Generator
    {
        yield 'Folder' => ['/folders', Dossier::class, ['nom' => 'Mon dossier']];
        yield 'Note' => ['/notes', Note::class, ['nom' => 'Ma note', 'contenu' => 'Contenu']];
        yield 'Contact' => ['/contacts', Contact::class, ['nom' => 'Dupont', 'prenom' => 'Jean']];
        yield 'Event' => ['/events', Evenement::class, ['nom' => 'Rendez-vous', 'date' => (new \DateTime('+1 day'))->format(\DateTimeInterface::ATOM)]];
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DonneePersonnelle> $class
     * @param array<string, string>           $body
     */
    public function testBeneficiaryCreatesItemForThemself(string $endpoint, string $class, array $body): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $otherBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $item = $this->create(BeneficiaryFixture::BENEFICIARY_MAIL, $endpoint, $class, [...$body, 'beneficiaire_id' => $otherBeneficiary->getId()]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($beneficiary->getId(), $item->getBeneficiaire()->getId());
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DonneePersonnelle> $class
     * @param array<string, string>           $body
     */
    public function testProCreatesItemForManagedBeneficiary(string $endpoint, string $class, array $body): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        $item = $this->create(MemberFixture::MEMBER_WITH_CLIENT, $endpoint, $class, [...$body, 'beneficiaire_id' => $beneficiary->getId()]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($beneficiary->getId(), $item->getBeneficiaire()->getId());
    }

    /**
     * @dataProvider provideResources
     *
     * @param array<string, string> $body
     */
    public function testProCanNotCreateItemForUnmanagedBeneficiary(string $endpoint, string $class, array $body): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_POST, $endpoint, ['json' => [...$body, 'beneficiaire_id' => $beneficiary->getId()]]);

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @dataProvider provideResources
     *
     * @param array<string, string> $body
     */
    public function testProMustGiveBeneficiary(string $endpoint, string $class, array $body): void
    {
        $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_POST, $endpoint, ['json' => $body]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testBeneficiaryCreatesSubFolderInheritingPrivacy(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $parentFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => true]);

        /** @var Dossier $folder */
        $folder = $this->create(BeneficiaryFixture::BENEFICIARY_MAIL, '/folders', Dossier::class, ['nom' => 'Sous-dossier', 'b_prive' => false, 'dossier_parent_id' => $parentFolder->getId()]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($parentFolder->getId(), $folder->getDossierParent()?->getId());
        $this->assertTrue($folder->getBPrive());
    }

    public function testCanNotCreateSubFolderInAnotherBeneficiaryFolder(): void
    {
        $otherBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);
        $otherFolder = FolderFactory::createOne(['beneficiaire' => $otherBeneficiary, 'bPrive' => false]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_POST, '/folders', ['json' => ['nom' => 'Sous-dossier', 'dossier_parent_id' => $otherFolder->getId()]]);

        $this->assertResponseStatusCodeSame(400);
    }

    /**
     * @param class-string<DonneePersonnelle> $class
     * @param array<string, mixed>            $body
     */
    private function create(string $email, string $endpoint, string $class, array $body): ?DonneePersonnelle
    {
        $response = $this->requestAsUser($email, Request::METHOD_POST, $endpoint, ['json' => $body]);
        $id = $response->toArray(false)['id'] ?? null;
        $this->em->clear();

        return $id ? $this->em->find($class, $id) : null;
    }
}
