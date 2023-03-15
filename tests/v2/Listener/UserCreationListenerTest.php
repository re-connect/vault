<?php

namespace App\Tests\v2\Listener;

use App\Entity\Beneficiaire;
use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\AuthenticatedTestCase;
use Zenstruck\Foundry\Test\Factories;

class UserCreationListenerTest extends AuthenticatedTestCase
{
    use Factories;

    /** @dataProvider provideListenerShouldFormatUsername */
    public function testListenerShouldFormatUsername(string $typeUser, string $regex): void
    {
        $user = UserFactory::createOne([
            'typeUser' => $typeUser,
        ])->object();

        if (User::USER_TYPE_BENEFICIAIRE === $typeUser) {
            /** @var Beneficiaire $beneficary */
            $beneficary = BeneficiaireFactory::createOne(['user' => $user])->object();
            $beneficary->setDateNaissance(new \DateTime());
        }

        self::assertMatchesRegularExpression($regex, $user->getUsername());
    }

    public function provideListenerShouldFormatUsername(): \Generator
    {
        yield 'Should trigger listener with beneficiary' => [
            User::USER_TYPE_BENEFICIAIRE,
            '/^[a-z\-]{1,}\.[a-z\-]{1,}\.([0-3][0-9]\/[0-1][0-9]\/[1-2][0-9]{3}){1}(-[1-9]\d*)?$/',
        ];
        yield 'Should trigger listener with member' => [
            User::USER_TYPE_MEMBRE,
            '/^[a-z\-]{1,}\.[a-z\-]{1,}(-[1-9]\d*)?$/',
        ];
    }
}
