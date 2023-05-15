<?php

namespace App\DataFixtures\v2;

use App\Entity\Centre;
use App\Entity\User;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MemberFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const MEMBER_MAIL = 'v2_test_user_membre@mail.com';
    public const MEMBER_MAIL_WITH_RELAYS = 'v2_test_user_membre_relays@mail.com';
    public const MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES = 'v2_test_user_membre_relays_shared_with_beneficiaries@mail.com';
    public const MEMBER_MAIL_WITH_UNIQUE_RELAY_SHARED_WITH_BENEFICIARIES = 'v2_test_user_membre_unique_relay_shared_with_beneficiaries@mail.com';
    public const MEMBER_PASSWORD_EXPIRED_MAIL = 'v2_test_user_membre_password_expired@mail.com';
    public const MEMBER_PASSWORD_OVERDUE_MAIL = 'v2_test_user_membre_password_overdue@mail.com';

    public function load(ObjectManager $manager)
    {
        $this->createMember($this->getTestUser());
        $this->createMember($this->getTestUserWithOverduePassword());
        $this->createMember($this->getTestUserWithExpiredPassword());
        $this->createMember($this->getTestUserWithRelays(), RelayFactory::createMany(4));
        $this->createMember(
            $this->getTestUserWithRelaysSharedWithBeneficiary(),
            [
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1]),
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_2]),
            ],
        );
        $this->createMember(
            $this->getTestUserWithUniqueRelaySharedWithBeneficiary(),
            [
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1]),
            ],
        );
    }

    public function getTestUser(): User
    {
        return UserFactory::createOne([
            'email' => self::MEMBER_MAIL,
        ])->object();
    }

    public function getTestUserWithExpiredPassword(): User
    {
        return UserFactory::createOne([
            'passwordUpdatedAt' => (new \DateTimeImmutable())->sub(new \DateInterval('P2Y')),
            'email' => self::MEMBER_PASSWORD_EXPIRED_MAIL,
        ])->object();
    }

    public function getTestUserWithOverduePassword(): User
    {
        return UserFactory::createOne([
            'passwordUpdatedAt' => (new \DateTimeImmutable())->sub(new \DateInterval('P360D')),
            'email' => self::MEMBER_PASSWORD_OVERDUE_MAIL,
        ])->object();
    }

    public function getTestUserWithRelays(): User
    {
        return UserFactory::createOne([
            'email' => self::MEMBER_MAIL_WITH_RELAYS,
        ])->object();
    }

    public function getTestUserWithRelaysSharedWithBeneficiary(): User
    {
        return UserFactory::createOne([
            'email' => self::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES,
        ])->object();
    }

    public function getTestUserWithUniqueRelaySharedWithBeneficiary(): User
    {
        return UserFactory::createOne([
            'email' => self::MEMBER_MAIL_WITH_UNIQUE_RELAY_SHARED_WITH_BENEFICIARIES,
        ])->object();
    }

    /** @param array<Centre> $relays */
    public function createMember(User $user, array $relays = []): void
    {
        if (!empty($relays)) {
            MembreFactory::new()
                ->linkToRelays($relays, true, true)
                ->withAttributes(['user' => $user])
                ->create();

            return;
        }

        MembreFactory::new()
            ->linkToRelays([RelayFactory::findOrCreate(['nom' => RelayFixture::DEFAULT_PRO_RELAY])])
            ->withAttributes(['user' => $user])
            ->create();
    }

    /** @return string[]     */
    public static function getGroups(): array
    {
        return ['v2'];
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [RelayFixture::class];
    }
}
