<?php

namespace App\Tests\v2\EventSubscriber;

use App\Entity\Beneficiaire;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\RelayFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\ModelFactory;

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

    /**
     * @dataProvider provideTestAddCreatorRelay
     */
    public function testAddCreatorRelay(ModelFactory $modelFactory): void
    {
        $relay = RelayFactory::createOne()->object();
        $subject = $modelFactory
            ->linkToRelays([$relay])
            ->create()
            ->object();

        $this->assertEquals($relay, $subject->getUser()->getCreatorCentreRelay());
    }

    public function provideTestAddCreatorRelay(): \Generator
    {
        yield 'test' => [BeneficiaireFactory::new()];
        yield 'test2' => [MembreFactory::new()];
    }
}
