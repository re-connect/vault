<?php

namespace App\DataFixtures\v2;

use App\Tests\Factory\ClientFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

class ClientFixture extends Fixture implements FixtureGroupInterface
{
    public const CLIENTS = [
        [
            'id' => '3vh0datc33s44w0sscks4c0ck0o4oog4ow8skkswgkk4owc04k',
            'secret' => '1bhcu6uphfz4gc4cwo48ok4gkswgss04gokwgkkws0o8sgos44',
            'name' => 'rosalie',
            'grantType' => 'client_credentials',
            'scopes' => 'centers beneficiaries beneficiaries_create beneficiaries_read documents documents_read documents_create folders_read notes contacts_read contacts_create pros events events_read events_create users notes_read notes_create',
        ],
        [
            'id' => 'm5tu4aqwedscccs4sogcgkk8w0wccg8s8c8ogwkso8w8c0ws0',
            'secret' => '4x0abo81dlkw8oso8gs8cg44gow000c0s8g8gwsw4g48880c8g',
            'name' => 'reconnect_pro',
            'grantType' => 'client_credentials',
            'scopes' => 'centers beneficiaries beneficiaries_read beneficiaries_update documents documents_read documents_update folders_read notes contacts_read contacts_update pros events events_read events_update users notes_read notes_update',
        ],
        [
            'id' => '1e5430a7f64ab17d3aea672f9eca115b',
            'secret' => '113c94c430f13cf346cf8b7b981649c36d4f710e7382b76968ec429c5df9da66a5bcaa981feac23b5d5936bde7b27855addc7ba0e8a20925a73481592698c94d',
            'name' => 'applimobile',
            'grantType' => 'password',
            'scopes' => '',
        ],
        [
            'id' => 'read_and_update_id',
            'secret' => 'read_and_update_secret',
            'name' => 'read_and_update_client',
            'grantType' => 'client_credentials',
            'scopes' => 'beneficiaries_read beneficiaries_update contacts_read contacts_update documents_read documents_update folders_read events_read events_update notes_read notes_update',
        ],
        [
            'id' => 'read_only_id',
            'secret' => 'read_only_secret',
            'name' => 'read_only_client',
            'grantType' => 'client_credentials',
            'scopes' => 'beneficiaries_read contacts_read documents_read events_read notes_read folders_read',
        ],
        [
            'id' => 'create_only_id',
            'secret' => 'create_only_secret',
            'name' => 'create_only_client',
            'grantType' => 'client_credentials',
            'scopes' => 'beneficiaries_create contacts_create documents_create events_create notes_create',
        ],
        [
            'id' => 'read_personal_data_id',
            'secret' => 'read_personal_data_secret',
            'name' => 'read_personal_data_client',
            'grantType' => 'client_credentials',
            'scopes' => 'personal_data_read',
        ],
        [
            'id' => 'create_personal_data_id',
            'secret' => 'create_personal_data_secret',
            'name' => 'create_personal_data_client',
            'grantType' => 'client_credentials',
            'scopes' => 'personal_data_create',
        ],
        [
            'id' => 'update_personal_data_id',
            'secret' => 'update_personal_data_secret',
            'name' => 'update_personal_data_client',
            'grantType' => 'client_credentials',
            'scopes' => 'personal_data_update',
        ],
        [
            'id' => 'no_scopes_id',
            'secret' => 'no_scopes_secret',
            'name' => 'no_scopes_client',
            'grantType' => 'client_credentials',
            'scopes' => '',
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach (self::CLIENTS as $client) {
            $oauthClient = (new Client($client['name'], $client['id'], $client['secret']))
                ->setGrants(new Grant($client['grantType']))
                ->setScopes(new Scope($client['scopes']));
            $manager->persist($oauthClient);

            ClientFactory::createOne(
                [
                    'nom' => $client['name'],
                    'randomId' => $client['id'],
                    'secret' => $client['secret'],
                    'allowedGrantTypes' => [$client['grantType']],
                    'newClientIdentifier' => $client['id'],
                ]
            );
        }

        $manager->flush();
    }

    /** @return string[] */
    #[\Override]
    public static function getGroups(): array
    {
        return ['v2'];
    }
}
