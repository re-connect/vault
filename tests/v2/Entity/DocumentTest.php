<?php

namespace App\Tests\v2\Entity;

use App\Entity\Attributes\Document;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    private ?Document $document = null;
    private ?string $nameWithoutExtension = null;
    private ?string $nom = null;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->document = new Document();
        $this->nameWithoutExtension = 'Nom';
        $this->nom = 'Nom.jpeg';
    }

    public function testGetNameWithoutExtension()
    {
        $this->document->setNom($this->nom);
        $this->assertEquals($this->nameWithoutExtension, $this->document->getNameWithoutExtension());

        $this->nom = 'Nom';
        $this->document->setNom($this->nom);
        $this->assertEquals($this->nameWithoutExtension, $this->document->getNameWithoutExtension());
    }
}
