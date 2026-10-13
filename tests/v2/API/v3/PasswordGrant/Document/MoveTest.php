<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Document;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class MoveTest extends AbstractPasswordGrantApiTestCase
{
    public function testBeneficiaryMovesDocumentIntoFolder(): void
    {
        $document = $this->createDocument(false);
        $folder = $this->createFolder(BeneficiaryFixture::BENEFICIARY_MAIL, false);

        $this->move(BeneficiaryFixture::BENEFICIARY_MAIL, $document, $folder);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['id' => $document->getId()]);
        $document = $this->findDocument($document->getId());
        $this->assertSame($folder->getId(), $document->getDossier()?->getId());
        $this->assertFalse($document->getBPrive());
    }

    public function testDocumentMovedIntoPrivateFolderBecomesPrivate(): void
    {
        $document = $this->createDocument(false);
        $folder = $this->createFolder(BeneficiaryFixture::BENEFICIARY_MAIL, true);

        $this->move(BeneficiaryFixture::BENEFICIARY_MAIL, $document, $folder);

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->findDocument($document->getId())->getBPrive());
    }

    public function testBeneficiaryMovesDocumentBackToRoot(): void
    {
        $folder = $this->createFolder(BeneficiaryFixture::BENEFICIARY_MAIL, false);
        $document = $this->createDocument(false, $folder);

        $this->move(BeneficiaryFixture::BENEFICIARY_MAIL, $document, null);

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->findDocument($document->getId())->getDossier());
    }

    public function testCanNotMoveDocumentIntoAnotherBeneficiaryFolder(): void
    {
        $document = $this->createDocument(false);
        $otherFolder = $this->createFolder(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, false);

        $this->move(BeneficiaryFixture::BENEFICIARY_MAIL, $document, $otherFolder);

        $this->assertResponseStatusCodeSame(400);
        $this->assertNull($this->findDocument($document->getId())->getDossier());
    }

    public function testProMovesSharedDocumentIntoSharedFolder(): void
    {
        $document = $this->createDocument(false);
        $folder = $this->createFolder(BeneficiaryFixture::BENEFICIARY_MAIL, false);

        $this->move(MemberFixture::MEMBER_WITH_CLIENT, $document, $folder);

        $this->assertResponseIsSuccessful();
        $this->assertSame($folder->getId(), $this->findDocument($document->getId())->getDossier()?->getId());
    }

    public function testProCanNotMoveDocumentIntoPrivateFolder(): void
    {
        $document = $this->createDocument(false);
        $folder = $this->createFolder(BeneficiaryFixture::BENEFICIARY_MAIL, true);

        $this->move(MemberFixture::MEMBER_WITH_CLIENT, $document, $folder);

        $this->assertResponseStatusCodeSame(403);
        $this->assertNull($this->findDocument($document->getId())->getDossier());
    }

    public function provideForbiddenMoves(): \Generator
    {
        yield 'Pro on a private document' => [MemberFixture::MEMBER_WITH_CLIENT, true];
        yield 'Another beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, false];
    }

    /** @dataProvider provideForbiddenMoves */
    public function testCanNotMoveDocument(string $email, bool $isPrivate): void
    {
        $document = $this->createDocument($isPrivate);
        $folder = $this->createFolder(BeneficiaryFixture::BENEFICIARY_MAIL, false);

        $this->move($email, $document, $folder);

        $this->assertResponseStatusCodeSame(403);
        $this->assertNull($this->findDocument($document->getId())->getDossier());
    }

    private function createDocument(bool $isPrivate, ?Dossier $folder = null): Document
    {
        return DocumentFactory::createOne([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
            'bPrive' => $isPrivate,
            'dossier' => $folder,
        ]);
    }

    private function createFolder(string $beneficiaryEmail, bool $isPrivate): Dossier
    {
        return FolderFactory::createOne(['beneficiaire' => BeneficiaireFactory::findByEmail($beneficiaryEmail), 'bPrive' => $isPrivate]);
    }

    private function move(string $email, Document $document, ?Dossier $folder): void
    {
        $this->requestAsUser($email, Request::METHOD_PATCH, sprintf('/documents/%d/move', $document->getId()), [
            'json' => ['folder_id' => $folder?->getId()],
        ]);
    }

    private function findDocument(int $id): Document
    {
        $this->em->clear();

        return $this->em->find(Document::class, $id);
    }
}
