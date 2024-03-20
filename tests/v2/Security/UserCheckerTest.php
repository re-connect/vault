<?php

namespace App\Tests\v2\Security;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Security\UserChecker;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserCheckerTest extends KernelTestCase
{
    private UserChecker $userChecker;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->userChecker = $container->get(UserChecker::class);
    }

    public function testDisableUserThrowException(): void
    {
        $user = UserFactory::findOrCreate(['enabled' => false])->object();

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->userChecker->checkPostAuth($user);
    }

    public function testEnableUserDoesNotThrowException(): void
    {
        $user = UserFactory::findOrCreate(['enabled' => true])->object();

        // Test will generate error if userChecker trows an exception
        $this->expectNotToPerformAssertions();
        $this->userChecker->checkPostAuth($user);
    }

    public function testInCreationUserThrowException(): void
    {
        $user = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_IN_CREATION)->object()->getUser();

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->userChecker->checkPostAuth($user);
    }

    public function testNotInCreationUserDoesNotThrowException(): void
    {
        $user = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object()->getUser();

        $this->expectNotToPerformAssertions();
        $this->userChecker->checkPostAuth($user);
    }
}
