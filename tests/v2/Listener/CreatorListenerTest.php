<?php

namespace App\Tests\v2\Listener;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\GestionnaireFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\AuthenticatedKernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CreatorListenerTest extends AuthenticatedKernelTestCase
{
    use Factories;

    public function provideTestCreatorListener(): ?\Generator
    {
        yield 'Should trigger listener when creating as beneficiary' => [
            BeneficiaryFixture::BENEFICIARY_MAIL,
        ];
        yield 'Should trigger listener when creating as professional' => [
            MemberFixture::MEMBER_MAIL,
        ];
        yield 'Should trigger listener when creating as gestionnaire' => [
            GestionnaireFixture::GESTIONNAIRE_MAIL,
        ];
    }

    /** @dataProvider provideTestCreatorListener */
    public function testContactCreator(string $email): void
    {
        $user = UserFactory::find(['email' => $email])->object();
        $this->loginUser($email);

        ContactFactory::createOne()->object();

        self::assertSame(ContactFactory::last()->object()->getCreatorUser()->getEntity()->getId(), $user->getId());
    }

    /** @dataProvider provideTestCreatorListener */
    public function testNoteCreator(string $email): void
    {
        $user = UserFactory::find(['email' => $email])->object();
        $this->loginUser($email);

        NoteFactory::createOne()->object();

        self::assertSame(NoteFactory::last()->object()->getCreatorUser()->getEntity()->getId(), $user->getId());
    }

    /** @dataProvider provideTestCreatorListener */
    public function testEventCreator(string $email): void
    {
        $user = UserFactory::find(['email' => $email])->object();
        $this->loginUser($email);

        EventFactory::createOne()->object();

        self::assertSame(EventFactory::last()->object()->getCreatorUser()->getEntity()->getId(), $user->getId());
    }

    /** @dataProvider provideTestCreatorListener */
    public function testFolderCreator(string $email): void
    {
        $user = UserFactory::find(['email' => $email])->object();
        $this->loginUser($email);

        FolderFactory::createOne()->object();

        self::assertSame(FolderFactory::last()->object()->getCreatorUser()->getEntity()->getId(), $user->getId());
    }

    /** @dataProvider provideTestCreatorListener */
    public function testDocumentCreator(string $email): void
    {
        $user = UserFactory::find(['email' => $email])->object();
        $this->loginUser($email);

        DocumentFactory::createOne()->object();

        self::assertSame(DocumentFactory::last()->object()->getCreatorUser()->getEntity()->getId(), $user->getId());
    }
}
