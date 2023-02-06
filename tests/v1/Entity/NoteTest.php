<?php

namespace App\Tests\v1\Entity;

use App\Entity\Beneficiaire;
use App\Entity\Note;
use PHPUnit\Framework\TestCase;

class NoteTest extends TestCase
{
    protected Note $note;

    /**
     * @throws \Exception
     */
    public function testGetterAndSetter()
    {
        $this->assertNull($this->note->getId());

        $date = new \DateTime();

        // Donnée personnelle

        $this->note->setBPrive(true);
        $this->assertTrue($this->note->getBPrive());

        $this->note->setBPrive(false);
        $this->assertFalse($this->note->getBPrive());

        $this->note->setNom('Nom');
        $this->assertEquals('Nom', $this->note->getNom());

        $this->note->setCreatedAt($date);
        $this->assertEquals($date, $this->note->getCreatedAt());

        $this->note->setUpdatedAt($date);
        $this->assertEquals($date, $this->note->getUpdatedAt());

        $this->assertEquals($this->note->getNom(), $this->note);

        // Note

        $this->note->setContenu('Contenu');
        $this->assertEquals('Contenu', $this->note->getContenu());
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->note = new Note(new Beneficiaire());
    }
}
