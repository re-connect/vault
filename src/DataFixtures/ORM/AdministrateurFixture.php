<?php

namespace App\DataFixtures\ORM;

use App\Entity\Administrateur;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class AdministrateurFixture extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $em)
    {
        $user = new User();
        $user->setUsername('pleuh');
        $user->setPlainPassword('mister');
        $user->setPassword('mister');
        $user->setPrenom('Paul-Louis');
        $user->setNom('Bertret');
        $user->setEmail('rythag@gmail.com');
        $user->setTelephone('0664514293');
        $user->setLastIp('127.0.0.1');
        $user->setEnabled(true);
        $user->addRole('ROLE_ADMIN');
        $user->addRole('ROLE_SONATA_ADMIN');
        $user->setPasswordUpdatedAt(new \DateTimeImmutable());

        $admin = new Administrateur();
        $admin->setUser($user);
        $em->persist($admin);

        $user = new User();
        $user->setUsername('reconnect');
        $user->setPlainPassword('reconnect');
        $user->setPassword('reconnect');
        $user->setPrenom('Admin');
        $user->setNom('Admin');
        $user->setEmail('admin@admin.com');
        $user->setTelephone('0606060606');
        $user->setLastIp('127.0.0.1');
        $user->setEnabled(true);
        $user->addRole('ROLE_ADMIN');
        $user->addRole('ROLE_SONATA_ADMIN');
        $user->setPasswordUpdatedAt(new \DateTimeImmutable());

        $admin = new Administrateur();
        $admin->setUser($user);
        $em->persist($admin);

        $em->flush();
    }

    /** @return string[] */
    public static function getGroups(): array
    {
        return ['v1'];
    }
}
