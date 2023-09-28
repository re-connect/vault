<?php

namespace App\DataFixtures\v2;

use App\Entity\Beneficiaire;
use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
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
    public const BENEFICIARY_MAIL_IN_CREATION = 'v2_test_user_beneficiary_in_creation@mail.com';
    public const BENEFICIARY_PHONE = '0612345678';

    public function load(ObjectManager $manager)
    {
        $this->createTestBeneficiary(
            $this->getTestUser(self::BENEFICIARY_MAIL)->setTelephone(self::BENEFICIARY_PHONE),
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
        $this->createTestBeneficiary($this->getTestUser(self::BENEFICIARY_MAIL_IN_CREATION), [], [], true);

        $manager->flush();
    }

    public function createTestBeneficiary(User $user, array $attributes = [], array $relays = [], bool $inCreation = false): void
    {
        $beneficiary = BeneficiaireFactory::new($attributes)
            ->linkToRelays(!empty($relays)
                ? $relays
                : [RelayFactory::findOrCreate(['nom' => RelayFixture::DEFAULT_PRO_RELAY])]
            )
            ->withAttributes(['user' => $user])
            ->create()->object();

        $this->addPersonalData($beneficiary);
        $this->addCreators($user);
        $this->initCreationProcess($beneficiary, $inCreation);
    }

    private function initCreationProcess(Beneficiaire $beneficiary, bool $inCreation = false): void
    {
        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate(['beneficiary' => $beneficiary])->object();
        $creationProcess->setIsCreating($inCreation);
    }

    private function addPersonalData(Beneficiaire $beneficiary): void
    {
        ContactFactory::createOne(['beneficiaire' => $beneficiary])->object();
        NoteFactory::createOne(['beneficiaire' => $beneficiary])->object();
        EventFactory::createOne(['beneficiaire' => $beneficiary])->object();
        DocumentFactory::createOne(['beneficiaire' => $beneficiary])->object();
        FolderFactory::createMany(2, ['beneficiaire' => $beneficiary]);
    }

    private function addCreators(User $user): void
    {
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
