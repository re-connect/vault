<?php

namespace App\Tests\v2\Entity;

use App\Entity\Document;
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

    /** @dataProvider provideNamesForExtensionRemoval */
    public function testRemoveExtension(string $name, string $expectedName): void
    {
        self::assertSame($expectedName, Document::removeExtension($name));
    }

    public static function provideNamesForExtensionRemoval(): \Generator
    {
        yield 'Lowercase extension' => ['rapport.pdf', 'rapport'];
        yield 'Uppercase extension' => ['photo.JPEG', 'photo'];
        yield 'Only the last extension' => ['archive.tar.gz', 'archive.tar'];
        yield 'No extension' => ['rapport', 'rapport'];
        yield 'Suffix with a space is not an extension' => ['Report. v2', 'Report. v2'];
        yield 'Suffix longer than 4 characters is not an extension' => ['rapport.final', 'rapport.final'];
        yield 'Dot without a name before it' => ['.pdf', '.pdf'];
    }
}
