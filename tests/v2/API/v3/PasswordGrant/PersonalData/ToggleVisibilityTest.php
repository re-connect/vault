<?php

namespace App\Tests\v2\API\v3\PasswordGrant\PersonalData;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\DonneePersonnelle;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class ToggleVisibilityTest extends AbstractPasswordGrantApiTestCase
{
    public function provideResources(): \Generator
    {
        yield 'Document' => ['/documents', DocumentFactory::class];
        yield 'Folder' => ['/folders', FolderFactory::class];
        yield 'Note' => ['/notes', NoteFactory::class];
        yield 'Contact' => ['/contacts', ContactFactory::class];
        yield 'Event' => ['/events', EventFactory::class];
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testProMakesSharedItemPrivate(string $endpoint, string $factory): void
    {
        $item = $this->createItem($factory, false);

        $this->toggle(MemberFixture::MEMBER_WITH_CLIENT, $endpoint, $item);

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->findItem($factory, $item->getId())->getBPrive());
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testProCanNotToggleAPrivateItem(string $endpoint, string $factory): void
    {
        $item = $this->createItem($factory, true);

        $this->toggle(MemberFixture::MEMBER_WITH_CLIENT, $endpoint, $item);

        $this->assertResponseStatusCodeSame(403);
        $this->assertTrue($this->findItem($factory, $item->getId())->getBPrive());
    }

    /**
     * @dataProvider provideResources
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testAnotherBeneficiaryCanNotToggleItem(string $endpoint, string $factory): void
    {
        $item = $this->createItem($factory, false);

        $this->toggle(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, $endpoint, $item);

        $this->assertResponseStatusCodeSame(403);
        $this->assertFalse($this->findItem($factory, $item->getId())->getBPrive());
    }

    /** @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory */
    private function createItem(string $factory, bool $isPrivate): DonneePersonnelle
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        return $factory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => $isPrivate]);
    }

    private function toggle(string $email, string $endpoint, DonneePersonnelle $item): void
    {
        $this->requestAsUser($email, Request::METHOD_PATCH, sprintf('%s/%d/toggle-visibility', $endpoint, $item->getId()), [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{}',
        ]);
    }

    /** @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory */
    private function findItem(string $factory, int $id): DonneePersonnelle
    {
        $this->em->clear();

        return $this->em->find($factory::class(), $id);
    }
}
