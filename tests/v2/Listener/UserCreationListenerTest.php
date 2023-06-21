<?php

namespace App\Tests\v2\Listener;

use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserCreationListenerTest extends KernelTestCase
{
    private ?EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testListenerShouldFormatBeneficiaryUsername(): void
    {
        $beneficiary = BeneficiaireFactory::createOne()->object();
        $user = $beneficiary->getUser();

        $this->assertUsername($user);

        // Check that username is updated with different information
        $beneficiary->setDateNaissance(new \DateTime());
        $this->em->flush();
        $this->assertUsername($user);

        $user->setNom('newLastname');
        $this->em->flush();
        $this->assertUsername($user);

        $user->setPrenom('newFirstname');
        $this->em->flush();
        $this->assertUsername($user);

        // Check that username is updated with shorter information
        $user->setPrenom('newFirstnam');
        $this->em->flush();
        $this->assertUsername($user);
    }

    public function testListenerShouldFormatBeneficiaryUsernameWithHomonymSuffix(): void
    {
        $beneficiary = BeneficiaireFactory::createOne()->object();
        $user = $beneficiary->getUser();

        // We create user with same information
        $beneficiaryHomonym = BeneficiaireFactory::createOne(['dateNaissance' => $beneficiary->getDateNaissance()])->object();
        $userHomonym = $beneficiaryHomonym->getUser();
        $userHomonym->setNom($user->getNom())->setPrenom($user->getPrenom());
        $this->em->flush();

        $this->assertUsername($user);
        // Username should have suffix
        $this->assertUsername($userHomonym, 1);

        // If we modify information, username should not have suffix
        $userHomonym->setNom('randomLastname');
        $this->em->flush();
        $this->assertUsername($userHomonym);
    }

    public function testListenerShouldFormatProUsername(): void
    {
        $pro = MembreFactory::createOne()->object();
        $user = $pro->getUser();

        $this->assertUsername($user);

        // Check that username is updated with different information
        $user->setNom('newLastname');
        $this->em->flush();
        $this->assertUsername($user);

        $user->setPrenom('newFirstname');
        $this->em->flush();
        $this->assertUsername($user);

        // Check that username is updated with shorter information
        $user->setPrenom('newFirstnam');
        $this->em->flush();
        $this->assertUsername($user);
    }

    public function testListenerShouldFormatProUsernameWithHomonymSuffix(): void
    {
        $pro = MembreFactory::createOne()->object();
        $user = $pro->getUser();

        // We create user with same information
        $proHomonym = MembreFactory::createOne()->object();
        $userHomonym = $proHomonym->getUser();
        $userHomonym->setNom($user->getNom())->setPrenom($user->getPrenom());
        $this->em->flush();

        $this->assertUsername($user);
        // Username should have suffix
        $this->assertUsername($userHomonym, 1);

        // If we modify information, username should not have suffix
        $userHomonym->setNom('randomLastname');
        $this->em->flush();
        $this->assertUsername($userHomonym);
    }

    private function assertUsername(User $user, int $duplicateNumber = null): void
    {
        $beneficiary = $user->getSubjectBeneficiaire();

        self::assertEquals(
            $user->getUsername(),
            $beneficiary
                ? strtolower(
                    sprintf('%s.%s.%s%s',
                        $user->getPrenom(),
                        $user->getNom(),
                        $beneficiary->getDateNaissance()->format('d/m/Y'),
                        $duplicateNumber ? sprintf('-%d', $duplicateNumber) : '',
                    ))
                : strtolower(
                    sprintf('%s.%s%s',
                        $user->getNom(),
                        $user->getPrenom(),
                        $duplicateNumber ? sprintf('-%d', $duplicateNumber) : '',
                    ))
        );
    }
}
