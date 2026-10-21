<?php

namespace App\Tests\v2\API\v2\PersonalData;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\DonneePersonnelle;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\API\v2\AbstractApiV2Test;
use Doctrine\ORM\EntityManagerInterface;

class PersonalDataAccessTest extends AbstractApiV2Test
{
    private const string FORBIDDEN_NAME = 'renamed-by-unauthorized-user';

    /**
     * @param class-string $factory
     *
     * @dataProvider providePersonalDataRoutes
     */
    public function testMemberWithRelayInCommonCannotAccessPrivatePersonalData(string $factory, string $method, string $url, array $body = []): void
    {
        $personalData = $this->createOwnerPersonalData($factory, true);
        $personalDataId = $personalData->getId();
        $personalDataName = $personalData->getNom();

        $client = static::createClient();
        $this->loginAsUser($client, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES);
        $client->request($method, $this->generateUrl(sprintf($url, $personalDataId)), $body ? ['json' => $body] : []);

        self::assertResponseStatusCodeSame(403);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $personalData = $em->find($factory::class(), $personalDataId);
        self::assertNotNull($personalData);
        self::assertSame($personalDataName, $personalData->getNom());
        self::assertTrue($personalData->isPrivate());
    }

    /**
     * @param class-string $factory
     *
     * @dataProvider provideAuthorizedReads
     */
    public function testAuthorizedUserCanReadPersonalData(string $factory, string $url, string $userMail, bool $isPrivate): void
    {
        $personalData = $this->createOwnerPersonalData($factory, $isPrivate);

        $client = static::createClient();
        $this->loginAsUser($client, $userMail);
        $client->request('GET', $this->generateUrl(sprintf($url, $personalData->getId())));

        self::assertResponseStatusCodeSame(200);
    }

    public function providePersonalDataRoutes(): \Generator
    {
        yield 'note get' => [NoteFactory::class, 'GET', '/notes/%d'];
        yield 'note edit' => [NoteFactory::class, 'PUT', '/notes/%d', ['nom' => self::FORBIDDEN_NAME, 'contenu' => 'content']];
        yield 'note toggle access' => [NoteFactory::class, 'PATCH', '/notes/%d/toggle-access'];
        yield 'note delete' => [NoteFactory::class, 'DELETE', '/notes/%d'];
        yield 'contact get' => [ContactFactory::class, 'GET', '/contacts/%d'];
        yield 'contact edit' => [ContactFactory::class, 'PUT', '/contacts/%d', ['nom' => self::FORBIDDEN_NAME, 'prenom' => 'firstname']];
        yield 'contact toggle access' => [ContactFactory::class, 'PATCH', '/contacts/%d/toggle-access'];
        yield 'contact delete' => [ContactFactory::class, 'DELETE', '/contacts/%d'];
        yield 'event get' => [EventFactory::class, 'GET', '/events/%d'];
        yield 'event edit' => [EventFactory::class, 'PUT', '/events/%d', ['nom' => self::FORBIDDEN_NAME]];
        yield 'event toggle access' => [EventFactory::class, 'PATCH', '/events/%d/toggle-access'];
        yield 'event delete' => [EventFactory::class, 'DELETE', '/events/%d'];
        yield 'folder patch' => [FolderFactory::class, 'PATCH', '/folders/%d', ['nom' => self::FORBIDDEN_NAME]];
        yield 'folder rename' => [FolderFactory::class, 'PATCH', '/folders/%d/name', ['name' => self::FORBIDDEN_NAME]];
        yield 'folder send by email' => [FolderFactory::class, 'POST', '/folders/%d/send', ['email' => 'test@example.com']];
        yield 'folder toggle access' => [FolderFactory::class, 'PATCH', '/folders/%d/toggle-access'];
        yield 'folder delete' => [FolderFactory::class, 'DELETE', '/folders/%d'];
    }

    public function provideAuthorizedReads(): \Generator
    {
        foreach (['note' => [NoteFactory::class, '/notes/%d'], 'contact' => [ContactFactory::class, '/contacts/%d'], 'event' => [EventFactory::class, '/events/%d']] as $type => [$factory, $url]) {
            yield sprintf('Owner can read his private %s', $type) => [$factory, $url, BeneficiaryFixture::BENEFICIARY_MAIL, true];
            yield sprintf('Member with relay in common can read shared %s', $type) => [$factory, $url, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, false];
        }
    }

    /** @param class-string $factory */
    private function createOwnerPersonalData(string $factory, bool $isPrivate): DonneePersonnelle
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        return $factory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => $isPrivate]);
    }
}
