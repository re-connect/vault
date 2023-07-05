<?php

namespace App\Tests\v2\Listener;

use App\Entity\Beneficiaire;
use App\Entity\Membre;
use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
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
}
