<?php

namespace App\Tests\v2\API\v3\PasswordGrant\PersonalData;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class PersonalDataItemTest extends AbstractPasswordGrantApiTestCase
{
    public function provideAccesses(): \Generator
    {
        $resources = [
            'Document' => ['/documents', DocumentFactory::class],
            'Folder' => ['/folders', FolderFactory::class],
            'Note' => ['/notes', NoteFactory::class],
            'Contact' => ['/contacts', ContactFactory::class],
            'Event' => ['/events', EventFactory::class],
        ];

        foreach ($resources as $name => [$endpoint, $factory]) {
            yield sprintf('%s: beneficiary gets own private item', $name) => [$endpoint, $factory, BeneficiaryFixture::BENEFICIARY_MAIL, true, 200];
            yield sprintf('%s: pro gets shared item of a managed beneficiary', $name) => [$endpoint, $factory, MemberFixture::MEMBER_WITH_CLIENT, false, 200];
            yield sprintf('%s: pro can not get private item', $name) => [$endpoint, $factory, MemberFixture::MEMBER_WITH_CLIENT, true, 403];
            yield sprintf('%s: another beneficiary can not get item', $name) => [$endpoint, $factory, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, false, 403];
        }
    }

    /**
     * @dataProvider provideAccesses
     *
     * @param class-string<DocumentFactory|FolderFactory|NoteFactory|ContactFactory|EventFactory> $factory
     */
    public function testGetItem(string $endpoint, string $factory, string $email, bool $isPrivate, int $expectedStatus): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $item = $factory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => $isPrivate]);

        $this->requestAsUser($email, Request::METHOD_GET, sprintf('%s/%d', $endpoint, $item->getId()));

        $this->assertResponseStatusCodeSame($expectedStatus);
        if (200 === $expectedStatus) {
            $this->assertJsonContains(['id' => $item->getId()]);
        }
    }

    public function testBeneficiaryGetsOwnPastEvent(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $pastEvent = EventFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => false, 'date' => new \DateTime('-1 day')]);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_GET, sprintf('/events/%d', $pastEvent->getId()));

        $this->assertResponseIsSuccessful();
    }
}
