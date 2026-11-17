<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Security;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class UserSearchTest extends AbstractPasswordGrantApiTestCase
{
    public function provideUsers(): \Generator
    {
        yield 'Beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Member' => [MemberFixture::MEMBER_WITH_CLIENT];
    }

    /** @dataProvider provideUsers */
    public function testUserCanNotSearchBeneficiariesByUsername(string $email): void
    {
        $otherBeneficiary = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $this->requestAsUser($email, Request::METHOD_GET, sprintf('/users?username=%s', $otherBeneficiary->getUsername()));

        $this->assertResponseStatusCodeSame(403);
    }
}
