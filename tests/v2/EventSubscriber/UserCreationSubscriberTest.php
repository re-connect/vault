<?php

namespace App\Tests\v2\EventSubscriber;

use App\Entity\Beneficiaire;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\RelayFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserCreationSubscriberTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::getContainer();
    }

    public function testFormatUsername(): void
    {
        /** @var Beneficiaire $beneficiary */
        $beneficiary = BeneficiaireFactory::createOne()->object();
        $user = $beneficiary->getUser();
        $expectedUsername = sprintf('%s.%s.%s',
            strtolower($user->getPrenom()),
            strtolower($user->getNom()),
            $beneficiary->getDateNaissanceStr()
        );
        $this->assertEquals($expectedUsername, $user->getUsername());
    }

    public function testAddCreatorRelay(): void
    {
        $relay = RelayFactory::createOne()->object();
        /** @var Beneficiaire $beneficiary */
        $beneficiary = BeneficiaireFactory::new()
            ->linkToRelays([$relay])
            ->create()
            ->object();
        $user = $beneficiary->getUser();

        $this->assertEquals($relay, $user->getCreatorCentreRelay());
    }
}
