<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Security;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class UserPasswordUpdateTest extends AbstractPasswordGrantApiTestCase
{
    public function testUserCanNotSetWeakPassword(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT);
        $hashedPassword = $user->getPassword();

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, Request::METHOD_PATCH, sprintf('/users/%d', $user->getId()), [
            'json' => ['plain_password' => 'weak'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame($hashedPassword, UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT)->getPassword());
    }

    public function testUserCanSetStrongPassword(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT);
        $hashedPassword = $user->getPassword();

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, Request::METHOD_PATCH, sprintf('/users/%d', $user->getId()), [
            'json' => ['plain_password' => 'NewStrongPassword1!'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertNotSame($hashedPassword, UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT)->getPassword());
    }
}
