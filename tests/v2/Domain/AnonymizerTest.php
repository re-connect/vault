<?php

namespace App\Tests\v2\Domain;

use App\Domain\Anonymization\DataAnonymizer\PersonalDataAnonymizer;
use App\Domain\Anonymization\DataAnonymizer\UserAnonymizer;
use App\Domain\Anonymization\FixtureGenerator;
use App\Entity\User;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\NoteFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class AnonymizerTest extends KernelTestCase
{
    use Factories;

    private UserAnonymizer $userAnonymizer;
    private PersonalDataAnonymizer $personalDataAnonymizer;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->userAnonymizer = $container->get(UserAnonymizer::class);
        $this->personalDataAnonymizer = $container->get(PersonalDataAnonymizer::class);
        parent::setUp();
    }

    public function testAnonymizeUsers(): void
    {
        $users = $this->em->getRepository(User::class)->findAnonymizables();
        $this->userAnonymizer->anonymizeUsers($users);

        foreach ($users as $user) {
            $this->em->refresh($user);
            $email = $user->getEmail();
            $phone = $user->getTelephone();

            self::assertFalse($user->isTest());
            self::assertTrue(in_array($user->getPrenom(), FixtureGenerator::RANDOM_FIRST_NAMES));
            self::assertTrue(in_array($user->getNom(), FixtureGenerator::RANDOM_LAST_NAMES));

            if ($email) {
                self::assertStringNotContainsString('@reconnect.fr', $email);
                self::assertStringContainsString('@yopmail', $email);
            }

            if ($phone) {
                self::assertTrue(in_array($phone, FixtureGenerator::RANDOM_PHONE_NUMBERS));
            }
        }
    }

    public function testAnonymizeDocuments(): void
    {
        $this->personalDataAnonymizer->anonymizeDocuments();
        $documents = DocumentFactory::all();

        foreach ($documents as $document) {
            $this->assertEquals('anonymous.png', $document->getObjectKey());
            $this->assertEquals('anonymous-thumbnail.png', $document->getThumbnailKey());
            $this->assertEquals('Document anonymisé', $document->getNom());
            $this->assertEquals('png', $document->getExtension());
        }
    }

    public function testAnonymizeNotes(): void
    {
        $this->personalDataAnonymizer->anonymizeNotes();
        $notes = NoteFactory::all();

        foreach ($notes as $note) {
            $this->assertEquals(FixtureGenerator::ANONYMIZED_SUBJECT, $note->getNom());
            $this->assertEquals(FixtureGenerator::ANONYMIZED_CONTENT, $note->getContenu());
        }
    }

    public function testAnonymizeContacts(): void
    {
        $this->personalDataAnonymizer->anonymizeContacts();
        $contacts = ContactFactory::all();

        foreach ($contacts as $contact) {
            $email = $contact->getEmail();
            $phone = $contact->getTelephone();
            $comment = $contact->getCommentaire();
            $this->assertTrue(in_array($contact->getNom(), FixtureGenerator::RANDOM_LAST_NAMES));
            $this->assertTrue(in_array($contact->getPrenom(), FixtureGenerator::RANDOM_FIRST_NAMES));

            if ($email) {
                $this->assertStringContainsString('@yopmail.fr', $email);
            }
            if ($phone) {
                $this->assertTrue(in_array($phone, FixtureGenerator::RANDOM_PHONE_NUMBERS));
            }
            if ($comment) {
                $this->assertEquals(FixtureGenerator::ANONYMIZED_CONTENT, $comment);
            }
        }
    }

    public function testAnonymizeEvents(): void
    {
        $this->personalDataAnonymizer->anonymizeEvents();
        $events = EventFactory::all();

        foreach ($events as $event) {
            $comment = $event->getCommentaire();

            $this->assertEquals(FixtureGenerator::ANONYMIZED_SUBJECT, $event->getNom());

            if ($comment) {
                $this->assertEquals(FixtureGenerator::ANONYMIZED_CONTENT, $event->getCommentaire());
            }
        }
    }
}
