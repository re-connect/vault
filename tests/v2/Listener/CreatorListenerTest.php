<?php

namespace App\Tests\v2\Listener;

use App\DataFixtures\v2\BeneficiaryFixture;
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
    }

    /** @dataProvider provideTestCreatorListener */
    public function testContactCreator(string $email): void
    {
        $user = UserFactory::find(['email' => $email])->_real();
        $this->loginUser($email);

        ContactFactory::createOne()->_real();

        self::assertSame(ContactFactory::last()->_real()->getCreatorUser()->getEntity()->getId(), $user->getId());
    }

    /** @dataProvider provideTestCreatorListener */
    public function testNoteCreator(string $email): void
    {
        $user = UserFactory::find(['email' => $email])->_real();
        $this->loginUser($email);

        NoteFactory::createOne()->_real();

        self::assertSame(NoteFactory::last()->_real()->getCreatorUser()->getEntity()->getId(), $user->getId());
    }

    /** @dataProvider provideTestCreatorListener */
    public function testEventCreator(string $email): void
    {
        $user = UserFactory::find(['email' => $email])->_real();
        $this->loginUser($email);

        EventFactory::createOne()->_real();

        self::assertSame(EventFactory::last()->_real()->getCreatorUser()->getEntity()->getId(), $user->getId());
    }

    /** @dataProvider provideTestCreatorListener */
    public function testFolderCreator(string $email): void
    {
        $user = UserFactory::find(['email' => $email])->_real();
        $this->loginUser($email);

        FolderFactory::createOne()->_real();

        self::assertSame(FolderFactory::last()->_real()->getCreatorUser()->getEntity()->getId(), $user->getId());
    }

    /** @dataProvider provideTestCreatorListener */
    public function testDocumentCreator(string $email): void
    {
        $user = UserFactory::find(['email' => $email])->_real();
        $this->loginUser($email);

        DocumentFactory::createOne()->_real();

        self::assertSame(DocumentFactory::last()->_real()->getCreatorUser()->getEntity()->getId(), $user->getId());
    }
}
