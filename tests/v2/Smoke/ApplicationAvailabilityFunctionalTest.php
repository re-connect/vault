<?php

namespace App\Tests\v2\Smoke;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Beneficiaire;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

class ApplicationAvailabilityFunctionalTest extends WebTestCase
{
    use Factories;
    private KernelBrowser $client;
    private ?Beneficiaire $beneficiary;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $this->client->loginUser($this->beneficiary->getUser());

        parent::setUp();
    }

    /**
     * @dataProvider urlProvider
     */
    public function testPageWithoutParametersIsSuccessful(string $url): void
    {
        $this->client->request('GET', $url);

        if ($this->client->getResponse()->getStatusCode() > 400) {
            var_dump($this->client->getResponse()->getContent());
        }

        $this->assertLessThan(400, $this->client->getResponse()->getStatusCode());
    }

    public function urlProvider(): \Generator
    {
        yield ['/'];
        yield ['/login'];
        yield ['/reconnect-accompagnement-numerique'];
        yield ['/reconnect-la-solution-pro'];
        yield ['/nous-contacter'];
        yield ['/reconnect-le-coffre-fort-numerique'];
        yield ['/faq-rgpd'];
        yield ['/login'];
        yield ['/reset-password/choose'];
        yield ['/reset-password/email'];
        yield ['/reset-password/sms'];
        yield ['/reset-password/check-sms'];
        yield ['/reset-password/reset/sms/{token}'];
        yield ['/reset-password/reset/email/{token}'];
        yield ['/user/settings'];
        yield ['/user/delete'];
        yield ['/logout'];
    }

    public function testBeneficiaryContactPages(): void
    {
        $contactId = ContactFactory::findOrCreate(['beneficiaire' => $this->beneficiary])->object()->getId();
        $contactTodeleteId = ContactFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId();
        $beneficiaryId = $this->beneficiary->getId();
        $contactUris = [
            sprintf('/beneficiary/%d/contacts', $beneficiaryId),
            sprintf('/beneficiary/%d/contact/create', $beneficiaryId),
            sprintf('/contact/%d/detail', $contactId),
            sprintf('/contact/%d/edit', $contactId),
            sprintf('/contact/%d/delete', $contactTodeleteId),
        ];
        foreach ($contactUris as $uri) {
            $this->client->request('GET', $uri);
            $this->assertLessThan(400, $this->client->getResponse()->getStatusCode());
        }
    }

    public function testBeneficiaryNotePages(): void
    {
        $noteId = NoteFactory::findOrCreate(['beneficiaire' => $this->beneficiary])->object()->getId();
        $noteTodeleteId = NoteFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId();
        $beneficiaryId = $this->beneficiary->getId();
        $noteUris = [
            sprintf('/beneficiary/%d/notes', $beneficiaryId),
            sprintf('/beneficiary/%d/note/create', $beneficiaryId),
            sprintf('/note/%d/detail', $noteId),
            sprintf('/note/%d/edit', $noteId),
            sprintf('/note/%d/delete', $noteTodeleteId),
        ];
        foreach ($noteUris as $uri) {
            $this->client->request('GET', $uri);
            $this->assertLessThan(400, $this->client->getResponse()->getStatusCode());
        }
    }

    public function testBeneficiaryEventPages(): void
    {
        $eventId = EventFactory::findOrCreate(['beneficiaire' => $this->beneficiary])->object()->getId();
        $eventToDeleteId = EventFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId();
        $beneficiaryId = $this->beneficiary->getId();
        $eventUris = [
            sprintf('/beneficiary/%d/events', $beneficiaryId),
            sprintf('/beneficiary/%d/event/create', $beneficiaryId),
            sprintf('/event/%d/detail', $eventId),
            sprintf('/event/%d/edit', $eventId),
            sprintf('/event/%d/delete', $eventToDeleteId),
        ];
        foreach ($eventUris as $uri) {
            $this->client->request('GET', $uri);
            $this->assertLessThan(400, $this->client->getResponse()->getStatusCode());
        }
    }

    public function testBeneficiaryDocumentPages(): void
    {
        $documentId = DocumentFactory::findOrCreate(['beneficiaire' => $this->beneficiary])->object()->getId();
        $documentToDeleteId = DocumentFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId();
        $beneficiaryId = $this->beneficiary->getId();
        $folderId = FolderFactory::findOrCreate(['beneficiaire' => $this->beneficiary])->object()->getId();

        $documentUris = [
            sprintf('/beneficiary/%d/documents', $beneficiaryId),
            sprintf('/document/%d/detail', $documentId),
            sprintf('/document/%d/rename', $documentId),
            sprintf('/document/%d/download', $documentId),
            sprintf('/documents/%d/move/folder/%d', $documentId, $folderId),
            sprintf('/document/%d/delete', $documentToDeleteId),
            sprintf('/document/%d/tree-view-move', $documentId),
        ];
        foreach ($documentUris as $uri) {
            $this->client->request('GET', $uri);
            $this->assertLessThan(400, $this->client->getResponse()->getStatusCode());
        }
        DocumentFactory::find(['id' => $documentId])->object()->setDossier();
    }

    public function testBeneficiaryFolderPages(): void
    {
        $firstFolderId = FolderFactory::findOrCreate(['beneficiaire' => $this->beneficiary])->object()->getId();
        $secondFolderId = FolderFactory::findOrCreate(['beneficiaire' => $this->beneficiary])->object()->getId();
        $folderToDeleteId = FolderFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId();
        $beneficiaryId = $this->beneficiary->getId();
        $folderUris = [
            sprintf('/folder/%d', $firstFolderId),
            sprintf('/beneficiary/%d/folder/create', $beneficiaryId),
            sprintf('/folder/%d/create-subfolder', $firstFolderId),
            sprintf('/folder/%d/rename', $firstFolderId),
            sprintf('/folders/%d/move-to-folder/%d', $firstFolderId, $secondFolderId),
            sprintf('/folder/%d/tree-view-move', $firstFolderId),
            sprintf('/folder/%d/delete', $folderToDeleteId),
        ];
        foreach ($folderUris as $uri) {
            $this->client->request('GET', $uri);
            $this->assertLessThan(400, $this->client->getResponse()->getStatusCode());
        }
        FolderFactory::find(['id' => $secondFolderId])->object()->setDossierParent();
    }

    public function testBeneficiaryRelayPages(): void
    {
        $beneficiaryId = $this->beneficiary->getId();
        $relayUris = [
            sprintf('/beneficiary/%d/relays', $beneficiaryId),
        ];
        foreach ($relayUris as $uri) {
            $this->client->request('GET', $uri);
            $this->assertLessThan(400, $this->client->getResponse()->getStatusCode());
        }
    }
}
