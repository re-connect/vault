<?php

namespace App\Tests\v2;

use App\Entity\Beneficiaire;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

class AuthenticatedTestCase extends WebTestCase
{
    use Factories;

    public function createBeneficiaryAndLogin(KernelBrowser $client, string $email): User
    {
        $beneficiary = $this->createTestBeneficiary($email);
        $user = $beneficiary->getUser();
        $client->loginUser($user);

        return $user;
    }

    public function createTestBeneficiary(string $email): Beneficiaire
    {
        $em = $this->getEntityManager();

        $beneficiary = (new Beneficiaire())
            ->setUser($this->getTestUser($email, ['ROLE_BENEFICIAIRE']))
            ->setQuestionSecrete('question')
            ->setReponseSecrete('reponse')
            ->setDateNaissance(new \DateTime())
            ->setLieuNaissance('Ici')
            ->setIsCreating(false);

        $em->persist($beneficiary);
        $em->flush();

        return $beneficiary;
    }

    /**
     * @param string[] $roles
     */
    public function createTestUser(string $email, array $roles, ?string $typeUser = null): User
    {
        $em = $this->getEntityManager();
        $user = $this->getTestUser($email, $roles);
        if ($typeUser) {
            $user->setTypeUser($typeUser);
        }
        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * @param string[] $roles
     */
    private function getTestUser(string $email, array $roles): User
    {
        return (new User())
            ->setUsername($email)
            ->setPassword('password')
            ->setPrenom('test')
            ->setNom('test')
            ->setEmail($email)
            ->setTelephone('0666666666')
            ->setLastIp('127.0.0.1')
            ->setEnabled(true)
            ->setRoles($roles)
            ->setPasswordUpdatedAt(new \DateTimeImmutable());
    }

    public function removeTestUser(string $email): void
    {
        $em = $this->getEntityManager();
        try {
            $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        } catch (\Exception) {
            return;
        }

        if ($user) {
            if ($user->getSubjectBeneficiaire()) {
                $em->remove($user->getSubjectBeneficiaire());
            }
            $em->remove($user);
            $em->flush();
        }
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
