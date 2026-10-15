<?php

namespace App\Tests\v2\API\v2\PasswordGrant\Security;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\DonneePersonnelle;
use App\Entity\Dossier;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\API\v2\PasswordGrant\AbstractPasswordGrantApiV2TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * A pro must not access the private personal data of a beneficiary they manage.
 */
class ProPrivateDataAccessTest extends AbstractPasswordGrantApiV2TestCase
{
    public function provideRequests(): \Generator
    {
        yield 'Share a private document' => [DocumentFactory::class, Request::METHOD_POST, '/documents/%d/share', ['email' => 'recipient@mail.com']];
        yield 'Rename a private folder' => [FolderFactory::class, Request::METHOD_PATCH, '/folders/%d', ['nom' => 'Renamed']];
        yield 'Get a private note' => [NoteFactory::class, Request::METHOD_GET, '/notes/%d', null];
        yield 'Delete a private note' => [NoteFactory::class, Request::METHOD_DELETE, '/notes/%d', null];
        yield 'Get a private contact' => [ContactFactory::class, Request::METHOD_GET, '/contacts/%d', null];
        yield 'Get a private event' => [EventFactory::class, Request::METHOD_GET, '/events/%d', null];
    }

    /**
     * @dataProvider provideRequests
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     * @param array<string, string>|null                                                          $body
     */
    public function testProCanNotAccessPrivateItem(string $factory, string $method, string $endpoint, ?array $body): void
    {
        $item = $factory::createOne([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
            'bPrive' => true,
            'nom' => 'Private item',
        ]);
        // Document names are normalized when stored
        $name = $item->getNom();

        $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, $method, sprintf($endpoint, $item->getId()), null === $body ? [] : ['json' => $body]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertEmailCount(0);
        $item = $this->findItem($factory, $item->getId());
        $this->assertNotNull($item);
        $this->assertSame($name, $item->getNom());
    }

    /** @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory */
    private function findItem(string $factory, int $id): DonneePersonnelle|Dossier|null
    {
        $this->em->clear();

        return $this->em->find($factory::class(), $id);
    }
}
