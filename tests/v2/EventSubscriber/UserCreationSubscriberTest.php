<?php

namespace App\Tests\v2\EventSubscriber;

use App\DataFixtures\v2\MemberFixture;
use App\Entity\Attributes\Beneficiaire;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\RelayFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Test\Factories;

class UserCreationSubscriberTest extends KernelTestCase
{
    use Factories;
    private Security|MockObject $securityMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityMock = $this->createMock(Security::class);
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
        $loggedUser = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS)->object()->getUser();
        $this->securityMock->method('getUser')->willReturn($loggedUser);
        self::getContainer()->set(Security::class, $this->securityMock);

        $relay = RelayFactory::createOne()->object();
        $subject = $modelFactory
            ->linkToRelays([$relay])
            ->create()
            ->object();

        $createdUser = $subject->getUser();
        $this->assertEquals(
            $createdUser->isBeneficiaire()
                ? $relay
                : null,
            $createdUser->getCreatorCentreRelay(),
        );
    }

    public function provideTestAddCreatorRelay(): \Generator
    {
        yield 'Creator relay should be the first affiliated relay for beneficiary' => [BeneficiaireFactory::new()];
        yield 'Pro user should not have creator relay' => [MembreFactory::new()];
    }
}
