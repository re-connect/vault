<?php

namespace App\Tests\v1\Entity;

use App\Entity\Document;
use App\Entity\Dossier;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    private ?Document $document = null;
    private ?Dossier $dossier = null;
    private ?int $taille = null;
    private ?string $nameWithoutExtension = null;
    private ?string $nom = null;
    private ?\DateTime $date = null;
    private ?string $url = null;
    private ?string $extension = null;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->document = new Document();
        $this->dossier = new Dossier();
        $this->taille = 3;
        $this->nameWithoutExtension = 'Nom';
        $this->nom = 'Nom.jpeg';
        $this->date = new \DateTime();
        $this->url = 'https://reconnect.fr';
        $this->extension = 'jpeg';
    }

    public function testGetNameWithoutExtension()
    {
        $this->document->setNom($this->nom);
        $this->assertEquals($this->nameWithoutExtension, $this->document->getNameWithoutExtension());

        $this->nom = 'Nom';
        $this->document->setNom($this->nom);
        $this->assertEquals($this->nameWithoutExtension, $this->document->getNameWithoutExtension());
    }

    public function testGetDateEmission()
    {
        $this->testSetDateEmission();
        $this->assertEquals($this->date, $this->document->getDateEmission());
    }

    public function testSetDateEmission()
    {
        $this->assertNull($this->document->getDateEmission());
        $this->document->setDateEmission($this->date);
        $this->assertNotNull($this->document->getDateEmission());
    }

    public function testGetDossier()
    {
        $this->testSetDossier();
        $this->assertEquals($this->dossier, $this->document->getDossier());
    }

    public function testSetDossier()
    {
        $this->assertNull($this->document->getDossier());
        $this->document->setDossier($this->dossier);
        $this->assertNotNull($this->document->getDossier());
    }

    public function testGetExtension()
    {
        $this->testSetExtension();
        $this->assertEquals($this->extension, $this->document->getExtension());
    }

    public function testSetExtension()
    {
        $this->assertEmpty($this->document->getExtension());
        $this->document->setExtension($this->extension);
        $this->assertNotEmpty($this->document->getExtension());
    }

    public function testGetUrl()
    {
        $this->testSetUrl();
        $this->assertEquals($this->url, $this->document->getUrl());
    }

    public function testSetUrl()
    {
        $this->assertNull($this->document->getUrl());
        $this->document->setUrl($this->url);
        $this->assertNotNull($this->document->getUrl());
    }

    public function testGetTaille()
    {
        $this->testSetTaille();
        $this->assertEquals($this->taille, $this->document->getTaille());
    }

    public function testSetTaille()
    {
        $this->assertNull($this->document->getTaille());
        $this->document->setTaille($this->taille);
        $this->assertNotNull($this->document->getTaille());
    }
}
