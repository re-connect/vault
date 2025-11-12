<?php

namespace App\Tests\v2\Listener;

use App\Tests\Factory\NoteFactory;
use App\Tests\v2\AuthenticatedKernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;

class SanitizeNoteContentListenerTest extends AuthenticatedKernelTestCase
{
    use Factories;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
    }

    public function provideTestSanitizeNote(): ?\Generator
    {
        yield 'Should not update content with no style' => [
            '<p><strong>coucou</strong></p>',
            null,
        ];
        yield 'Should not update content with only color rule' => [
            '<p><strong style="color: rgb(161, 0, 0);">coucou</strong></p>',
            null,
        ];
        yield 'Should remove style attribute on content with color + other rule' => [
            '<p><strong style="color: rgb(161, 0, 0); background-color: white;">coucou</strong></p>',
            '<p><strong>coucou</strong></p>',
        ];
        yield 'Should remove style attribute on content with only other rule' => [
            '<p><strong style="background-color: white;">coucou</strong></p>',
            '<p><strong>coucou</strong></p>',
        ];
    }

    /**
     * @dataProvider provideTestSanitizeNote
     */
    public function testSanitizeNoteOnCreate(string $content, ?string $updatedContent = null): void
    {
        $note = NoteFactory::createOne(['contenu' => $content])->object();

        self::assertEquals($updatedContent ?? $content, $note->getContenu());
    }

    /**
     * @dataProvider provideTestSanitizeNote
     */
    public function testSanitizeNoteOnUpdate(string $content, ?string $updatedContent = null): void
    {
        $note = NoteFactory::createOne()->object();

        $note->setContenu($content);
        $this->em->flush();

        self::assertEquals($updatedContent ?? $content, $note->getContenu());
    }
}
