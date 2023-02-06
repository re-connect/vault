<?php

namespace App\Tests\v1\Entity;

use App\Entity\Document;
use App\Entity\Dossier;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class DossierTest extends TestCase
{
    private Dossier $dossier;
    private Collection $documents;
    private Document $document;

    public function setUp(): void
    {
        $this->dossier = new Dossier();
        $this->documents = new ArrayCollection();
        $this->document = new Document();
        $this->documents[] = $this->document;
    }

    public function testGetDocuments()
    {
        $this->testAddDocument();
        $this->assertEquals($this->documents, $this->dossier->getDocuments());
    }

    public function testAddDocument()
    {
        $this->assertCount(0, $this->dossier->getDocuments());
        $this->dossier->addDocument($this->document);
        $this->assertCount(1, $this->dossier->getDocuments());
    }

    public function testRemoveDocument()
    {
        $this->assertCount(0, $this->dossier->getDocuments());
        $this->testAddDocument();
        $this->assertCount(1, $this->dossier->getDocuments());
        $this->dossier->removeDocument($this->document);
        $this->assertCount(0, $this->dossier->getDocuments());
    }
}
