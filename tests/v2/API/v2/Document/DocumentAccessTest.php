<?php

namespace App\Tests\v2\API\v2\Document;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Document;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\API\v2\AbstractApiV2Test;
use Doctrine\ORM\EntityManagerInterface;

class DocumentAccessTest extends AbstractApiV2Test
{
    private const string FORBIDDEN_NAME = 'renamed-by-unauthorized-user';

    /** @dataProvider provideDocumentRoutes */
    public function testOtherBeneficiaryCannotAccessDocument(string $method, string $url, array $body = []): void
    {
        $this->assertAccessDenied(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, false, $method, $url, $body);
    }

    /** @dataProvider provideDocumentRoutes */
    public function testMemberWithoutRelayInCommonCannotAccessDocument(string $method, string $url, array $body = []): void
    {
        $this->assertAccessDenied(MemberFixture::MEMBER_MAIL, false, $method, $url, $body);
    }

    /** @dataProvider provideDocumentRoutes */
    public function testMemberWithRelayInCommonCannotAccessPrivateDocument(string $method, string $url, array $body = []): void
    {
        $this->assertAccessDenied(MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, true, $method, $url, $body);
    }

    /** @dataProvider provideAuthorizedReads */
    public function testAuthorizedUserCanReadDocument(string $userMail, bool $isPrivate, string $url, int $expectedStatusCode): void
    {
        $document = $this->createOwnerDocument($isPrivate);

        $client = static::createClient();
        $this->loginAsUser($client, $userMail);
        $client->request('GET', $this->generateUrl(sprintf($url, $document->getId())));

        self::assertResponseStatusCodeSame($expectedStatusCode);
    }

    public function provideDocumentRoutes(): \Generator
    {
        yield 'show original' => ['GET', '/documents/%d'];
        yield 'show large version' => ['GET', '/documents/%d/large'];
        yield 'show json' => ['GET', '/documents/%d/json'];
        yield 'patch' => ['PATCH', '/documents/%d', ['nom' => self::FORBIDDEN_NAME]];
        yield 'rename' => ['PATCH', '/documents/%d/name', ['name' => self::FORBIDDEN_NAME]];
        yield 'send by email' => ['POST', '/documents/%d/send', ['email' => 'test@example.com']];
        yield 'share' => ['POST', '/documents/%d/share', ['email' => 'test@example.com']];
        yield 'toggle access' => ['PATCH', '/documents/%d/toggle-access'];
        yield 'put in folder' => ['PATCH', '/documents/%d/folder/%d'];
        yield 'get out from folder' => ['PATCH', '/documents/%d/get-out-from-folder'];
        yield 'delete' => ['DELETE', '/documents/%d'];
    }

    public function provideAuthorizedReads(): \Generator
    {
        yield 'Owner can show his shared document' => [BeneficiaryFixture::BENEFICIARY_MAIL, false, '/documents/%d', 302];
        yield 'Owner can read his private document' => [BeneficiaryFixture::BENEFICIARY_MAIL, true, '/documents/%d/json', 200];
        yield 'Member with relay in common can show shared document' => [MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, false, '/documents/%d', 302];
        yield 'Member with relay in common can read shared document' => [MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, false, '/documents/%d/json', 200];
    }

    private function assertAccessDenied(string $userMail, bool $isPrivate, string $method, string $url, array $body): void
    {
        $document = $this->createOwnerDocument($isPrivate);
        $folder = FolderFactory::createOne(['beneficiaire' => $document->getBeneficiaire(), 'bPrive' => false])->_real();
        $documentId = $document->getId();
        $documentName = $document->getNom();

        $client = static::createClient();
        $this->loginAsUser($client, $userMail);
        $client->request($method, $this->generateUrl(sprintf($url, $documentId, $folder->getId())), $body ? ['json' => $body] : []);

        self::assertResponseStatusCodeSame(403);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $document = $em->find(Document::class, $documentId);
        self::assertNotNull($document);
        self::assertSame($documentName, $document->getNom());
        self::assertSame($isPrivate, $document->getBPrive());
        self::assertNull($document->getDossier());
    }

    private function createOwnerDocument(bool $isPrivate): Document
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        return DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => $isPrivate, 'extension' => 'pdf'])->_real();
    }
}
