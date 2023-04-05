<?php

namespace App\Tests\v2\Entity\User;

use App\Entity\Beneficiaire;
use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Repository\UserRepository;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class IsTestUserTest extends TestCase
{
    use Factories;

    /** @dataProvider provideTestSubscriber */
    public function testTestSubscriber(bool $isCreatorRelayTest, bool $isFirstRelayTest, bool $isSecondRelayTest, bool $result): void
    {
        $creatorRelay = RelayFactory::createOne(['test' => $isCreatorRelayTest])->object();
        $firstRelay = RelayFactory::createOne(['test' => $isFirstRelayTest])->object();
        $secondRelay = RelayFactory::createOne(['test' => $isSecondRelayTest])->object();

        $user = UserFactory::createOne()->object();
        $beneficiary = BeneficiaireFactory::createOne(['user' => $user])->object();

        // We add test creator relay
        $beneficiary->addCreatorRelay($creatorRelay);

        // Affiliation with relay 1
        $beneficiaryRelay = $this->createBeneficiairyRelay($beneficiary, $firstRelay);
        $beneficiary->addBeneficiairesCentre($beneficiaryRelay);

        // Affiliation with relay 2
        $beneficiaryRelay = $this->createBeneficiairyRelay($beneficiary, $secondRelay);
        $beneficiary->addBeneficiairesCentre($beneficiaryRelay);

        self::assertEquals($user->isTest(), $result);
    }

    public function provideTestSubscriber(): ?\Generator
    {
        yield 'Should be test user with testCreator and affiliated with only test relays' => [true, true, true, true];
        yield 'Should not be test user with not test Creator and affiliated with only test relays' => [false, true, true, false];
        yield 'Should not be test user with testCreator and affiliated with only one test relay' => [true, true, false, false];
    }

    private function createBeneficiairyRelay(Beneficiaire $beneficiary, Centre $relay): BeneficiaireCentre
    {
        return (new BeneficiaireCentre())
            ->setUser(UserFactory::createOne()->object())
            ->setCentre($relay)
            ->setBeneficiaire($beneficiary);
    }
}
