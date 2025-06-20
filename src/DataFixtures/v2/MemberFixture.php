<?php

namespace App\DataFixtures\v2;

use App\Entity\Attributes\Centre;
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
    public const MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_MEMBER = 'v2_test_user_member_relays_shared_with_member@mail.com';
    public const MEMBER_MAIL_WITH_UNIQUE_RELAY_SHARED_WITH_BENEFICIARIES = 'v2_test_user_membre_unique_relay_shared_with_beneficiaries@mail.com';
    public const MEMBER_MAIL_NO_RELAY_NO_PERMISSION = 'v2_test_user_member_no_relay_no_permission@mail.com';
    public const MEMBER_PASSWORD_EXPIRED_MAIL = 'v2_test_user_membre_password_expired@mail.com';
    public const MEMBER_PASSWORD_OVERDUE_MAIL = 'v2_test_user_membre_password_overdue@mail.com';
    public const MEMBER_FIRST_VISIT = 'v2_test_user_membre_first_visit@mail.com';
    public const MEMBER_FIRST_VISIT_AND_EXPIRED_PASSWORD = 'v2_test_user_membre_first_visit_and_expired_password@mail.com';
    public const MEMBER_DISABLED = 'v2_test_user_membre_disabled@mail.com';
    public const MEMBER_PASSWORD_WEAK = 'v2_test_user_membre_weak_password@mail.com';
    public const MEMBER_FIRST_VISIT_WITH_MFA = 'v2_test_user_membre_first_visit_with_mfa@mail.com';
    public const MEMBER_WITH_MFA_ENABLE = 'v2_test_user_membre_with_mfa@mail.com';

    public const MEMBER_WITH_CLIENT = 'test_membre_with_client';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->createMember(['email' => self::MEMBER_MAIL],
            [
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_PRO_RELAY_1]),
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_PRO_RELAY_2]),
            ],
        );
        $this->createMember([
            'passwordUpdatedAt' => (new \DateTimeImmutable())->sub(new \DateInterval('P360D')),
            'email' => self::MEMBER_PASSWORD_OVERDUE_MAIL,
        ]);
        $this->createMember([
            'passwordUpdatedAt' => (new \DateTimeImmutable())->sub(new \DateInterval('P2Y')),
            'email' => self::MEMBER_PASSWORD_EXPIRED_MAIL,
        ]);
        $this->createMember(['email' => self::MEMBER_MAIL_WITH_RELAYS], RelayFactory::createMany(4));
        $this->createMember(['email' => self::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES],
            [
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1]),
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_2]),
            ],
        );
        $this->createMember(['email' => self::MEMBER_MAIL_WITH_UNIQUE_RELAY_SHARED_WITH_BENEFICIARIES],
            [
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1]),
            ],
        );
        $this->createMember(['email' => self::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_MEMBER],
            [
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_PRO_RELAY_1]),
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_PRO_RELAY_2]),
            ],
        );
        $this->createMember(['email' => self::MEMBER_MAIL_NO_RELAY_NO_PERMISSION], [], false, false);
        $this->createMember(['email' => self::MEMBER_FIRST_VISIT, 'firstVisit' => true, 'telephone' => null], [], false, false);
        $this->createMember(['email' => self::MEMBER_FIRST_VISIT_AND_EXPIRED_PASSWORD, 'firstVisit' => true, 'telephone' => null, 'passwordUpdatedAt' => new \DateTime('2 years ago')], [], false, false);
        $this->createMember(['email' => self::MEMBER_FIRST_VISIT_WITH_MFA, 'firstVisit' => true, 'mfaEnabled' => true], [], false, false);
        $this->createMember(['email' => self::MEMBER_DISABLED, 'enabled' => false]);
        $this->createMember([
            'email' => self::MEMBER_PASSWORD_WEAK,
            'hasPasswordWithLatestPolicy' => false,
            'password' => UserFactory::WEAK_PASSWORD_HASH,
        ]);
        $this->createMember(['email' => self::MEMBER_WITH_MFA_ENABLE, 'mfaEnabled' => true], [], false, false);
        $this->createMember(['email' => self::MEMBER_WITH_CLIENT],
            [
                RelayFactory::findOrCreate(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1]),
            ],
        );
    }

    /** @param array<Centre> $relays */
    public function createMember(array $userAttributes, array $relays = [], bool $beneficiaryManagement = true, bool $proManagement = true): void
    {
        $user = UserFactory::createOne($userAttributes);
        if (!empty($relays)) {
            MembreFactory::new()
                ->linkToRelays($relays, $beneficiaryManagement, $proManagement)
                ->withAttributes(['user' => $user])
                ->create();

            return;
        }

        MembreFactory::new()
            ->withAttributes(['user' => $user])
            ->create();
    }

    /** @return string[]     */
    #[\Override]
    public static function getGroups(): array
    {
        return ['v2'];
    }

    /** @return array<class-string<FixtureInterface>> */
    #[\Override]
    public function getDependencies(): array
    {
        return [RelayFixture::class];
    }
}
