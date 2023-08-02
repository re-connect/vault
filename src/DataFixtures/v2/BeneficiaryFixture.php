<?php

namespace App\DataFixtures\v2;

use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\CreatorCentreFactory;
use App\Tests\Factory\CreatorUserFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class BeneficiaryFixture extends Fixture implements FixtureGroupInterface
{
    public const BENEFICIARY_MAIL = 'v2_test_user_beneficiary@mail.com';
    public const BENEFICIARY_MAIL_SETTINGS = 'v2_test_user_beneficiary_settings@mail.com';
    public const BENEFICIARY_MAIL_SETTINGS_EDIT = 'v2_test_user_beneficiary_settings_edit@mail.com';
    public const BENEFICIARY_MAIL_SETTINGS_DELETE = 'v2_test_user_beneficiary_to_delete@mail.com';
    public const BENEFICIARY_MAIL_FIRST_VISIT = 'v2_test_user_beneficiary_first_visit@mail.com';
    public const BENEFICIARY_MAIL_NO_SECRET_QUESTION = 'v2_test_user_beneficiary_no_secret_question@mail.com';

    public function load(ObjectManager $manager)
    {
        $this->createTestBeneficiary(
            $this->getTestUser(self::BENEFICIARY_MAIL),
            [],
            [
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1]),
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_2]),
            ]
        );
        $this->createTestBeneficiary($this->getTestUser(self::BENEFICIARY_MAIL_SETTINGS));
        $this->createTestBeneficiary($this->getTestUser(self::BENEFICIARY_MAIL_SETTINGS_EDIT));
        $this->createTestBeneficiary($this->getTestUser(self::BENEFICIARY_MAIL_SETTINGS_DELETE));
        $this->createTestBeneficiary($this->getTestUser(self::BENEFICIARY_MAIL_FIRST_VISIT)->setFirstVisit(true));
        $this->createTestBeneficiary($this->getTestUser(self::BENEFICIARY_MAIL_NO_SECRET_QUESTION), ['questionSecrete' => null]);
    }

    public function createTestBeneficiary(User $user, array $attributes = [], array $relays = []): void
    {
        $beneficiary = BeneficiaireFactory::new($attributes)
            ->linkToRelays(!empty($relays)
                ? $relays
                : [RelayFactory::findOrCreate(['nom' => RelayFixture::DEFAULT_PRO_RELAY])]
            )
            ->withAttributes(['user' => $user])
            ->create()->object();

        ContactFactory::createOne(['beneficiaire' => $beneficiary])->object();
        NoteFactory::createOne(['beneficiaire' => $beneficiary])->object();
        EventFactory::createOne(['beneficiaire' => $beneficiary])->object();
        DocumentFactory::createOne(['beneficiaire' => $beneficiary])->object();
        FolderFactory::createMany(2, ['beneficiaire' => $beneficiary]);

        $creatorRelay = CreatorCentreFactory::createOne()->object();
        $creatorUser = CreatorUserFactory::createOne()->object();
        $user->addCreator($creatorRelay);
        $user->addCreator($creatorUser);
    }

    public function getTestUser(string $email): User
    {
        $username = strstr($email, '@', true);

        return UserFactory::createOne(['username' => $username, 'email' => $email])->object();
    }

    /** @return string[] */
    public static function getGroups(): array
    {
        return ['v2'];
    }
}
