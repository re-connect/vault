<?php

namespace App\DataFixtures\ORM;

use App\Entity\Client as OldClient;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

class ClientFixtures extends Fixture implements FixtureGroupInterface
{
    public const CLIENTS = [
        [
            'id' => '3vh0datc33s44w0sscks4c0ck0o4oog4ow8skkswgkk4owc04k',
            'secret' => '1bhcu6uphfz4gc4cwo48ok4gkswgss04gokwgkkws0o8sgos44',
            'name' => 'rosalie',
            'grantType' => 'client_credentials',
            'scopes' => 'centers beneficiaries documents notes contacts pros events',
        ],
        [
            'id' => 'm5tu4aqwedscccs4sogcgkk8w0wccg8s8c8ogwkso8w8c0ws0',
            'secret' => '4x0abo81dlkw8oso8gs8cg44gow000c0s8g8gwsw4g48880c8g',
            'name' => 'axel',
            'grantType' => 'client_credentials',
            'scopes' => '',
        ],
        [
            'id' => '1e5430a7f64ab17d3aea672f9eca115b',
            'secret' => '113c94c430f13cf346cf8b7b981649c36d4f710e7382b76968ec429c5df9da66a5bcaa981feac23b5d5936bde7b27855addc7ba0e8a20925a73481592698c94d',
            'name' => 'applimobile',
            'grantType' => 'password',
            'scopes' => '',
        ],
    ];

    public function load(ObjectManager $manager)
    {
        foreach (self::CLIENTS as $client) {
            $this->createClient($manager, $client['id'], $client['secret'], $client['name'], $client['grantType'], $client['scopes']);
        }

        $manager->flush();
    }

    private function createClient(ObjectManager $manager, string $id, string $secret, string $name, string $grantType, string $scopes = '')
    {
        $oldClient = (new OldClient())
            ->setAllowedGrantTypes([$grantType])
            ->setRandomId($id)
            ->setNewClientIdentifier($id)
            ->setSecret($secret)
            ->setDossierImage($name.'.png')
            ->setDossierNom($name)
            ->setNom($name)
            ->setActif(true);

        $clientAlreadyExisting2 = $manager->find(Client::class, $id);
        if (!$clientAlreadyExisting2) {
            $client = (new Client($name, $id, $secret))
                ->setGrants(new Grant($grantType))
                ->setScopes(new Scope($scopes));
            $manager->persist($client);
        }

        $manager->persist($oldClient);
    }

    /** @return string[] */
    public static function getGroups(): array
    {
        return ['v1'];
    }
}
