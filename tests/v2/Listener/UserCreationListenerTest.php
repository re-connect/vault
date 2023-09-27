<?php

namespace App\Tests\v2\Listener;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Beneficiaire;
use App\Entity\Membre;
use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\AuthenticatedTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;

class UserCreationListenerTest extends AuthenticatedTestCase
{
    use Factories;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testShouldFormatBeneficiaryUsername(): void
    {
        // Test standard username
        $beneficiary = BeneficiaireFactory::createOne()->object();
        $user = $beneficiary->getUser();
        $expectedUsername = strtolower(
            sprintf(
                '%s.%s.%s',
                $user->getPrenom(),
                $user->getNom(),
                $beneficiary->getDateNaissance()->format('d/m/Y'),
            )
        );

        self::assertEquals($expectedUsername, $user->getUsername());

        // Test create with duplicated information
        $newUser = (new User())
            ->setNom($user->getNom())
            ->setPrenom($user->getPrenom())
            ->setPassword('random')
            ->setTypeUser(User::USER_TYPE_BENEFICIAIRE);

        $newBeneficiary = (new Beneficiaire())
            ->setDateNaissance($beneficiary->getDateNaissance())
            ->setUser($newUser);
        $this->em->persist($newUser);
        $this->em->persist($newBeneficiary);
        $this->em->flush();

        $expectedUsername = sprintf('%s-1', $expectedUsername);

        self::assertEquals($expectedUsername, $newUser->getUsername());
    }

    public function testShouldFormatProUsername(): void
    {
        // Test standard username
        $pro = MembreFactory::createOne()->object();
        $user = $pro->getUser();
        $expectedUsername = strtolower(
            sprintf(
                '%s.%s',
                $user->getNom(),
                $user->getPrenom(),
            )
        );

        self::assertEquals($expectedUsername, $user->getUsername());

        // Test create with duplicated information
        $newUser = (new User())
            ->setNom($user->getNom())
            ->setPrenom($user->getPrenom())
            ->setPassword('random')
            ->setTypeUser(User::USER_TYPE_MEMBRE);

        $pro = (new Membre())
            ->setUser($newUser);
        $this->em->persist($newUser);
        $this->em->persist($pro);
        $this->em->flush();

        $expectedUsername = sprintf('%s-1', $user->getUsername());

        self::assertEquals($expectedUsername, $newUser->getUsername());
    }

    /** @dataProvider provideTestTriggerListener */
    public function testShouldTriggerListener(string $userMail): void
    {
        $user = UserFactory::findByEmail($userMail)->object();
        $baseUsername = $user->getUsername();

        $user->setNom('nom');
        $this->em->flush();
        $usernameAfterLastnameUpdate = $user->getUsername();
        self::assertNotEquals($baseUsername, $usernameAfterLastnameUpdate);

        $user->setPrenom('prenom');
        $this->em->flush();
        $usernameAfterFirstnameupdate = $user->getUsername();
        self::assertNotEquals($usernameAfterLastnameUpdate, $usernameAfterFirstnameupdate);

        if (BeneficiaryFixture::BENEFICIARY_MAIL === $userMail) {
            $user->getSubjectBeneficiaire()->setDateNaissance(new \DateTime('+1 day'));
            $this->em->flush();
            $usernameAfterBirthdateUpdate = $user->getUsername();

            self::assertNotEquals($usernameAfterFirstnameupdate, $usernameAfterBirthdateUpdate);
        }
    }

    /** @dataProvider provideTestTriggerListener */
    public function testShouldNotTriggerListener(string $userMail): void
    {
        $user = UserFactory::findByEmail($userMail)->object();
        $baseUsername = $user->getUsername();
        $user
            ->setEnabled(!$user->isEnabled())
            ->setPassword('newPassword')
            ->setRoles(['NEW_ROLE'])
            ->setLastLogin(new \DateTime())
            ->setTelephone('5555555555')
            ->setFirstVisit(!$user->isFirstVisit())
            ->setEmail('dummymail@mail.com')
            ->setLastLang('dd')
            ->setDisabledAt(new \DateTime())
            ->setDisabledBy(UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS)->object())
            ->setTest(!$user->isTest())
            ->setCanada($user->isCanada());
        $this->em->flush();

        $notUpdatedUsername = $user->getUsername();

        self::assertEquals($baseUsername, $notUpdatedUsername);
    }

    public function provideTestTriggerListener(): ?\Generator
    {
        yield 'Test trigger listener with beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Test trigger listener with pro' => [MemberFixture::MEMBER_MAIL];
    }
}
