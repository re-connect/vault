<?php

namespace App\Tests\v2\Smoke\PersonalData;

use App\Tests\v2\Smoke\AbstractSmokeTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Zenstruck\Foundry\Test\Factories;

class ApplicationAvailabilityFunctionalTest extends AbstractSmokeTest
{
    use Factories;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->loginUser($this->beneficiary->getUser());
    }

    /**
     * @dataProvider contactsUrlProvider
     */
    public function testContactsPages(string $url): void
    {
        $this->assertRoute($this->client, sprintf($url, $this->beneficiary->getContacts()[0]->getId()));
    }

    public function contactsUrlProvider(): \Generator
    {
        yield ['/contact/%d/detail'];
        yield ['/contact/%d/edit'];
        yield ['/contact/%d/delete'];
    }

    /**
     * @dataProvider notesUrlProvider
     */
    public function testNotesPages(string $url): void
    {
        $this->assertRoute($this->client, sprintf($url, $this->beneficiary->getNotes()[0]->getId()));
    }

    public function notesUrlProvider(): \Generator
    {
        yield ['/note/%d/detail'];
        yield ['/note/%d/edit'];
        yield ['/note/%d/delete'];
    }

    /**
     * @dataProvider eventsUrlProvider
     */
    public function testEventsPages(string $url): void
    {
        $this->assertRoute($this->client, sprintf($url, $this->beneficiary->getEvenements()[0]->getId()));
    }

    public function eventsUrlProvider(): \Generator
    {
        yield ['/event/%d/detail'];
        yield ['/event/%d/edit'];
        yield ['/event/%d/delete'];
    }

    /**
     * @dataProvider documentsUrlProvider
     */
    public function testDocumentsPages(string $url, bool $withFolder = false): void
    {
        $documentId = $this->beneficiary->getDocuments()[0]->getId();
        $folderId = $this->beneficiary->getDossiers()[0]->getId();

        $this->assertRoute($this->client, $withFolder
            ? sprintf($url, $documentId, $folderId)
            : sprintf($url, $documentId)
        );
    }

    public function documentsUrlProvider(): \Generator
    {
        yield ['/document/%d/detail'];
        yield ['/document/%d/rename'];
        yield ['/document/%d/download'];
        yield ['/document/%d/delete'];
        yield ['/document/%d/tree-view-move'];
        yield ['/documents/%d/move/folder/%d', true];
    }

    /**
     * @dataProvider folderUrlProvider
     */
    public function testFoldersPages(string $url, bool $withSecondFolder = false): void
    {
        $folderId = $this->beneficiary->getDossiers()[0]->getId();
        $secondFolderId = $this->beneficiary->getDossiers()[1]->getId();

        $this->assertRoute($this->client, $withSecondFolder
            ? sprintf($url, $folderId, $secondFolderId)
            : sprintf($url, $folderId)
        );
    }

    public function folderUrlProvider(): \Generator
    {
        yield ['/folder/%d/create-subfolder'];
        yield ['/folder/%d/rename'];
        yield ['/folder/%d/delete'];
        yield ['/folder/%d/tree-view-move'];
        yield ['/folder/%d/move-to-folder/%d', true];
    }
}
