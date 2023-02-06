<?php

namespace App\Manager;

use App\Entity\Adresse;
use App\Entity\Association;
use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\Gestionnaire;
use App\Entity\Membre;
use App\Entity\TypeCentre;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FixtureManager
{
    private EntityManagerInterface $em;
    private Generator $faker;
    private UserPasswordHasherInterface $hasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
    {
        $this->em = $em;
        $this->faker = FakerFactory::create('fr_FR');
        $this->hasher = $hasher;
    }

    public function getNewRandomBeneficiaire(UserManager $usermanager): Beneficiaire
    {
        $date = $this->faker->dateTimeThisCentury();
        $date->setTime(0, 0, 0);
        $user = $this->getNewRandomUser();
        $beneficiaire = (new Beneficiaire())
            ->setUser($user)
            ->setQuestionSecrete('question')
            ->setReponseSecrete('reponse')
            ->setDateNaissance($date)
            ->setLieuNaissance($this->faker->city())
            ->setIsCreating(false);

        return $beneficiaire;
    }

    public function getNewRandomUser($username = null): User
    {
        if (null === $username) {
            do {
                $username = $this->faker->userName();
            } while (null !== $this->em->getRepository(User::class)->findOneByUsername($username));
        }

        $password = 'password';
        $user = (new User())
            ->setUsername($username)
            ->setPlainPassword($password)
            ->setPrenom($this->faker->name())
            ->setNom($this->faker->name())
            ->setEmail($this->faker->email())
            ->setTelephone($this->faker->phoneNumber())
            ->setLastIp($this->faker->ipv4())
            ->setEnabled(true)
            ->setAdresse($this->getNewRandomAdresse())
            ->setPasswordUpdatedAt(new \DateTimeImmutable());
        $user->setPassword($this->hasher->hashPassword($user, $password));

        return $user;
    }

    public function createNewUser(string $username, string $typeUser, string $email, string $phone): User
    {
        $alreadyExistingUser = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
        if (null !== $alreadyExistingUser) {
            return $alreadyExistingUser;
        }

        $password = 'password';
        $user = (new User())
            ->setUsername($username)
            ->setPlainPassword($password)
            ->setPrenom($this->faker->name())
            ->setNom($this->faker->name())
            ->setTypeUser($typeUser)
            ->setEmail($email)
            ->setTelephone($phone)
            ->setEnabled(true);

        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function getNewRandomAdresse(): Adresse
    {
        return (new Adresse())
            ->setNom($this->faker->streetAddress())
            ->setVille($this->faker->city())
            ->setCodePostal($this->faker->postcode())
            ->setPays($this->faker->country())
            ->setLat($this->faker->latitude())
            ->setLng($this->faker->longitude());
    }

    public function getNewRandomCentre(): Centre
    {
        $typesCentres = $this->em->getRepository(TypeCentre::class)->findAll();
        $randomIndex = rand(0, count($typesCentres) - 1);

        return (new Centre())
            ->setNom($this->faker->company())
            ->setSiret($this->faker->firstName())
            ->setFiness($this->faker->randomNumber(9))
            ->setCode($this->faker->regexify('[A-Z0-9]{8}'))
            ->setAdresse($this->getNewRandomAdresse())
            ->setTypeCentre($typesCentres[$randomIndex]);
    }

    public function getNewRandomGestionnaire(): Gestionnaire
    {
        $user = $this->getNewRandomUser();

        return (new Gestionnaire())->setUser($user);
    }

    public function getNewRandomAssociation(): Association
    {
        $password = 'password';
        $userAssociation = (new User())
            ->setUsername($this->faker->userName())
            ->setPrenom($this->faker->name())
            ->setNom($this->faker->name())
            ->setPlainPassword($password)
            ->setLastIp($this->faker->ipv4())
            ->setEnabled(true);

        $userAssociation->setPassword($this->hasher->hashPassword($userAssociation, $password));

        return (new Association())
            ->setUser($userAssociation)
            ->setNom($this->faker->company())
            ->setCategorieJuridique(Association::ASSOCIATION_CATEGORIEJURIDIQUE_ASSOCIATION)
            ->setSiren($this->faker->userName())
            ->setUrlSite($this->faker->url());
    }

    public function getNewRandomMembre($username = null): Membre
    {
        $user = $this->getNewRandomUser($username);

        return (new Membre())->setUser($user);
    }
}
