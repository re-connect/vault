<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Document;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Document;
use App\Entity\SharedDocument;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ShareTest extends AbstractPasswordGrantApiTestCase
{
    public function provideAllowedShares(): \Generator
    {
        yield 'Beneficiary shares own private document' => [BeneficiaryFixture::BENEFICIARY_MAIL, true];
        yield 'Pro shares shared document' => [MemberFixture::MEMBER_WITH_CLIENT, false];
    }

    /** @dataProvider provideAllowedShares */
    public function testShareDocumentByEmail(string $email, bool $isPrivate): void
    {
        $document = $this->createDocument($isPrivate);

        $response = $this->share($email, $document, ['email' => 'recipient@mail.com']);

        $this->assertResponseStatusCodeSame(204);
        // The response must not expose the share token
        $this->assertSame('', $response->getContent());
        $this->assertEmailCount(1);
        $this->em->clear();
        $sharedDocument = $this->em->getRepository(SharedDocument::class)->findOneBy(['document' => $document->getId()]);
        $this->assertNotNull($sharedDocument);
        $this->assertSame('recipient@mail.com', $sharedDocument->getSharedWithEmail());
    }

    public function testCanNotShareWithoutEmail(): void
    {
        $this->share(BeneficiaryFixture::BENEFICIARY_MAIL, $this->createDocument(false), []);

        $this->assertResponseStatusCodeSame(400);
        $this->assertEmailCount(0);
    }

    public function provideForbiddenShares(): \Generator
    {
        yield 'Pro on a private document' => [MemberFixture::MEMBER_WITH_CLIENT, true];
        yield 'Another beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, false];
    }

    /** @dataProvider provideForbiddenShares */
    public function testCanNotShareDocument(string $email, bool $isPrivate): void
    {
        $this->share($email, $this->createDocument($isPrivate), ['email' => 'recipient@mail.com']);

        $this->assertResponseStatusCodeSame(403);
        $this->assertEmailCount(0);
    }

    private function createDocument(bool $isPrivate): Document
    {
        return DocumentFactory::createOne(['beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL), 'bPrive' => $isPrivate]);
    }

    /** @param array<string, string> $body */
    private function share(string $email, Document $document, array $body): ResponseInterface
    {
        return $this->requestAsUser($email, Request::METHOD_POST, sprintf('/documents/%d/share', $document->getId()), ['json' => $body]);
    }
}
