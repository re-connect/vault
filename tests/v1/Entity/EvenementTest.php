<?php

namespace App\Tests\v1\Entity;

use App\Entity\Beneficiaire;
use App\Entity\Evenement;
use App\Entity\Membre;
use App\Entity\SMS;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class EvenementTest extends TestCase
{
    protected Evenement $event;

    /**
     * @throws \Exception
     */
    public function testGetterAndSetter()
    {
        $this->assertNull($this->event->getId());

        $date = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

        // Donnée personnelle

        $this->event->setBPrive(true);
        $this->assertTrue($this->event->getBPrive());

        $this->event->setBPrive(false);
        $this->assertFalse($this->event->getBPrive());

        $this->event->setNom('Nom');
        $this->assertEquals('Nom', $this->event->getNom());

        $this->event->setCreatedAt($date);
        $this->assertEquals($date->format('c'), $this->event->getCreatedAt()->format('c'));

        $this->event->setUpdatedAt($date);
        $this->assertEquals($date->format('c'), $this->event->getUpdatedAt()->format('c'));

        // Evènement

        $this->event->setDate($date);
        $this->assertEquals($date->format('c'), $this->event->getDate()->format('c'));

        $this->event->setLieu('Lieu');
        $this->assertEquals('Lieu', $this->event->getLieu());

        $this->event->setCommentaire('Commentaire');
        $this->assertEquals('Commentaire', $this->event->getCommentaire());

        $rappels = new ArrayCollection([Evenement::EVENEMENT_RAPPEL_SMS => Evenement::EVENEMENT_RAPPEL_SMS]);

        $this->event->setRappels($rappels);
        $this->assertEquals($rappels, $this->event->getRappels());

        $this->event->setCommentaire('commentaire');
        $this->assertEquals('commentaire', $this->event->getCommentaire());

        $this->event->setBEnvoye(false);
        $this->assertFalse($this->event->getBEnvoye());

        $this->event->setHeureRappel(1);
        $this->assertEquals(1, $this->event->getHeureRappel());

        $this->event->setArchive(false);
        $this->assertFalse($this->event->getArchive());

        $this->assertEquals(sprintf('%s le %s', $this->event->getNom(), $this->event->getDate()->format('d/m/Y H:i')), $this->event);

        $sms = new SMS();
        $this->event->setSms($sms);
        $this->assertEquals($sms, $this->event->getSms());

        $user = new User();
        $this->event->setDeposePar($user);
        $this->assertEquals($user, $this->event->getDeposePar());

        $beneficiaire = new Beneficiaire();
        $this->event->setBeneficiaire($beneficiaire);
        $this->assertEquals($beneficiaire, $this->event->getBeneficiaire());

        $membre = new Membre();
        $this->event->setMembre($membre);
        $this->assertEquals($membre, $this->event->getMembre());

        // test du toString avec date
        $this->assertEquals(sprintf('%s le %s', $this->event->getNom(), $this->event->getDate()->format('d/m/Y H:i')), $this->event->__toString());

        // test du toString sans date
        $this->event->setDate(null);
        $this->assertEquals($this->event->getNom(), $this->event->__toString());
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->event = new Evenement(new Beneficiaire());
    }
}
