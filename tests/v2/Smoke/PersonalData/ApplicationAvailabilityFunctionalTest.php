<?php

namespace App\Tests\v2\Smoke\PersonalData;

use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\Smoke\AbstractSmokeTest;

class ApplicationAvailabilityFunctionalTest extends AbstractSmokeTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->loginUser($this->beneficiary->getUser());
    }

    /**
     * @dataProvider contactsUrlProvider
     */
    public function testContactsPages(string $url): void
    {
        $this->assertRoute(sprintf($url, ContactFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId()));
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
        $this->assertRoute(sprintf($url, NoteFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId()));
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
        $this->assertRoute(sprintf($url, EventFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId()));
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
        $documentId = DocumentFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId();
        $folderId = FolderFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId();

        $this->assertRoute($withFolder
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
        $folderId = FolderFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId();
        $secondFolderId = FolderFactory::createOne(['beneficiaire' => $this->beneficiary])->object()->getId();

        $this->assertRoute($withSecondFolder
            ? sprintf($url, $folderId, $secondFolderId)
            : sprintf($url, $folderId)
        );
    }

    public function folderUrlProvider(): \Generator
    {
        yield ['/folder/%d/create-subfolder'];
        yield ['/folder/%d/rename'];
        yield ['/folder/%d/download'];
        yield ['/folder/%d/delete'];
        yield ['/folder/%d/tree-view-move'];
        yield ['/folder/%d/move-to-folder/%d', true];
    }
}
