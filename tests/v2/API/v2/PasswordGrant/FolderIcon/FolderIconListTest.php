<?php

namespace App\Tests\v2\API\v2\PasswordGrant\FolderIcon;

use App\Entity\FolderIcon;
use App\Tests\v2\API\v2\PasswordGrant\AbstractPasswordGrantApiV2TestCase;
use Symfony\Component\HttpFoundation\Request;

class FolderIconListTest extends AbstractPasswordGrantApiV2TestCase
{
    public function testListIsPublic(): void
    {
        $client = static::createClient();
        $icon = (new FolderIcon())->setName('Santé')->setFileName('health.svg');
        $this->em->persist($icon);
        $this->em->flush();

        $client->request(Request::METHOD_GET, '/public/folder_icons');

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([[
            'id' => $icon->getId(),
            'name' => 'Santé',
            'url' => $icon->getPublicFilePath(),
        ]]);
    }
}
