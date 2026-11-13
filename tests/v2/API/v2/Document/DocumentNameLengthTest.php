<?php

namespace App\Tests\v2\API\v2\Document;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Document;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\v2\AbstractApiV2Test;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentNameLengthTest extends AbstractApiV2Test
{
    private const string V1_BASE_URL = '/appli';

    /** @dataProvider provideRenameRoutes */
    public function testRenameIsRefusedWhenNameIsTooLong(string $version, string $method, string $pathSuffix, string $field): void
    {
        $document = $this->createOwnerDocument();
        $documentName = $document->getNom();

        $this->requestAsOwner($version, $method, '/documents/'.$document->getId().$pathSuffix, [$field => str_repeat('a', Document::NAME_MAX_LENGTH + 1)]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame($documentName, $this->findDocument($document->getId())->getNom());
    }

    /** @dataProvider provideRenameRoutes */
    public function testRenameIsAcceptedWhenNameHasTheMaximumLength(string $version, string $method, string $pathSuffix, string $field): void
    {
        $document = $this->createOwnerDocument();
        $maximumLengthNameWithExtension = str_repeat('a', Document::NAME_MAX_LENGTH).'.pdf';

        $this->requestAsOwner($version, $method, '/documents/'.$document->getId().$pathSuffix, [$field => $maximumLengthNameWithExtension]);

        self::assertResponseStatusCodeSame(200);
        self::assertSame($maximumLengthNameWithExtension, $this->findDocument($document->getId())->getNom());
    }

    /** @dataProvider provideApiVersions */
    public function testUploadIsRefusedWhenOneNameIsTooLong(string $version): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $documentCount = DocumentFactory::count(['beneficiaire' => $beneficiary]);
        $validFile = new UploadedFile('tests/test-file.pdf', 'valid.pdf', 'application/pdf');
        $tooLongFileName = str_repeat('a', Document::NAME_MAX_LENGTH + 1).'.pdf';
        $tooLongFile = new UploadedFile('tests/test-file.pdf', $tooLongFileName, 'application/pdf');

        // The valid file comes first: it must not be saved either
        $this->requestAsOwner($version, 'POST', '/beneficiaries/'.$beneficiary->getId().'/documents', [], ['files' => [$validFile, $tooLongFile]]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame($documentCount, DocumentFactory::count(['beneficiaire' => $beneficiary]));
    }

    public function testThirdPartyAppKeepsTooLongName(): void
    {
        $clientName = 'reconnect_pro';
        $document = DocumentFactory::createOne([
            'beneficiaire' => $this->getBeneficiaryForClient($clientName),
            'bPrive' => false,
            'extension' => 'pdf',
        ]);
        $tooLongName = str_repeat('a', Document::NAME_MAX_LENGTH + 1).'.pdf';

        $client = static::createClient();
        $this->loginAsClient($client, $clientName);
        $client->request('PATCH', $this->generateUrl('/documents/'.$document->getId()), ['json' => ['nom' => $tooLongName]]);

        self::assertResponseStatusCodeSame(200);
        self::assertSame($tooLongName, $this->findDocument($document->getId())->getNom());
    }

    public static function provideRenameRoutes(): \Generator
    {
        yield 'v1 rename' => ['v1', 'PATCH', '/name', 'name'];
        yield 'v2 rename' => ['v2', 'PATCH', '/name', 'name'];
        yield 'v2 patch' => ['v2', 'PATCH', '', 'nom'];
    }

    public static function provideApiVersions(): \Generator
    {
        yield 'v1' => ['v1'];
        yield 'v2' => ['v2'];
    }

    /**
     * The v1 routes (/appli) are behind the session firewall, the v2 routes (/api/v2) behind OAuth.
     *
     * @param array<string, mixed>          $parameters
     * @param array<string, UploadedFile[]> $files
     */
    private function requestAsOwner(string $version, string $method, string $path, array $parameters, array $files = []): void
    {
        $client = static::createClient();

        if ('v1' === $version) {
            $browser = $client->getKernelBrowser();
            $browser->loginUser(UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL));
            $browser->request($method, self::V1_BASE_URL.$path, $parameters, $files);

            return;
        }

        $this->loginAsUser($client, BeneficiaryFixture::BENEFICIARY_MAIL);
        $options = $files
            ? ['headers' => ['Content-Type' => 'multipart/form-data'], 'extra' => ['files' => $files]]
            : ['json' => $parameters];
        $client->request($method, $this->generateUrl($path), $options);
    }

    private function createOwnerDocument(): Document
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        return DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => true, 'extension' => 'pdf']);
    }

    private function findDocument(int $documentId): Document
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->find(Document::class, $documentId);
    }
}
