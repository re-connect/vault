<?php

namespace App\Tests\v1\Entity;

use App\Entity\Beneficiaire;
use App\Entity\Contact;
use PHPUnit\Framework\TestCase;

class ContactTest extends TestCase
{
    protected Contact $contact;

    /**
     * @throws \Exception
     */
    public function testGetterAndSetter()
    {
        $this->assertNull($this->contact->getId());

        // Donnée personnelle

        $this->contact->setBPrive(true);
        $this->assertTrue($this->contact->getBPrive());

        $this->contact->setBPrive(false);
        $this->assertFalse($this->contact->getBPrive());

        $this->contact->setNom('Nom');
        $this->assertEquals('Nom', $this->contact->getNom());

        $date = new \DateTime();

        $this->contact->setCreatedAt($date);
        $this->assertEquals($date, $this->contact->getCreatedAt());

        $this->contact->setUpdatedAt($date);
        $this->assertEquals($date, $this->contact->getUpdatedAt());

        // Contact

        $this->contact->setPrenom('Prénom');
        $this->assertEquals('Prénom', $this->contact->getPrenom());

        $this->contact->setTelephone('0123456789');
        $this->assertEquals('0123456789', $this->contact->getTelephone());

        $this->contact->setEmail('prenom.nom@reconnect.fr');
        $this->assertEquals('prenom.nom@reconnect.fr', $this->contact->getEmail());

        $this->contact->setCommentaire('commentaire');
        $this->assertEquals('commentaire', $this->contact->getCommentaire());

        $this->contact->setAssociation('association');
        $this->assertEquals('association', $this->contact->getAssociation());
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->contact = new Contact(new Beneficiaire());
    }
}
