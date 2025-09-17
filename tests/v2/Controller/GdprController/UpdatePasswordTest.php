<?php

namespace App\Tests\v2\Controller\GdprController;

use App\DataFixtures\v2\AdminFixture;
use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\DataFixtures\v2\SuperAdminFixture;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UpdatePasswordTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private UserPasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    private const URL = '/update-password';

    public function provideTestRoute(): \Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as member' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
        yield 'Should return 200 status code when authenticated as beneficiary' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
    }

    public function provideTestFormIsValid(): \Generator
    {
        yield 'Should redirect when passwords match with required criteria for standard user' => [
            self::URL,
            'submit',
            [
                'change_password_form[plainPassword][first]' => '123456Aaaa',
                'change_password_form[plainPassword][second]' => '123456Aaaa',
            ],
            MemberFixture::MEMBER_MAIL,
            '/login-end',
        ];

        yield 'Should redirect when passwords match with required criteria for admin' => [
            self::URL,
            'submit',
            [
                'change_password_form[plainPassword][first]' => '123456Aaaa1111111111',
                'change_password_form[plainPassword][second]' => '123456Aaaa1111111111',
            ],
            AdminFixture::ADMIN_MAIL,
            '/login-end',
        ];

        yield 'Should redirect when passwords match with required criteria for super admin' => [
            self::URL,
            'submit',
            [
                'change_password_form[plainPassword][first]' => '123456Aaaa1111111111',
                'change_password_form[plainPassword][second]' => '123456Aaaa1111111111',
            ],
            SuperAdminFixture::SUPER_ADMIN_MAIL,
            '/login-end',
        ];
    }

    public function provideTestFormIsNotValid(): \Generator
    {
        $values = [
            'change_password_form[plainPassword][first]' => '123456Aa',
            'change_password_form[plainPassword][second]' => '123457Az',
        ];
        yield 'Should return an error if passwords does not match' => [
            self::URL,
            'app_update_password',
            'submit',
            $values,
            [
                [
                    'message' => 'passwords_mismatch',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];
        $values = [
            'change_password_form[plainPassword][first]' => '1234',
            'change_password_form[plainPassword][second]' => '1234',
        ];
        yield 'Should return an error if password is too short' => [
            self::URL,
            'app_update_password',
            'submit',
            $values,
            [
                [
                    'message' => 'password_help_criteria',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];

        $values = [
            'change_password_form[plainPassword][first]' => '1234567890TooShort!',
            'change_password_form[plainPassword][second]' => '1234567890TooShort!',
        ];
        yield 'Should return an error if password is 19 char long for admin' => [
            self::URL,
            'app_update_password',
            'submit',
            $values,
            [
                [
                    'message' => 'Votre mot de passe doit contenir au moins 20 caractères',
                    'params' => null,
                ],
            ],
            AdminFixture::ADMIN_MAIL,
        ];

        yield 'Should return an error if password is 19 char long for super admin' => [
            self::URL,
            'app_update_password',
            'submit',
            $values,
            [
                [
                    'message' => 'Votre mot de passe doit contenir au moins 20 caractères',
                    'params' => null,
                ],
            ],
            SuperAdminFixture::SUPER_ADMIN_MAIL,
        ];

        $values = [
            'change_password_form[plainPassword][first]' => '123456789',
            'change_password_form[plainPassword][second]' => '123456789',
        ];

        yield 'Should return an error if password contains only numbers' => [
            self::URL,
            'app_update_password',
            'submit',
            $values,
            [
                [
                    'message' => 'password_help_criteria',
                    'params' => null,
                ],
                [
                    'message' => 'password_criterion_lowercase',
                    'params' => null,
                ],
                [
                    'message' => 'password_criterion_uppercase',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];

        $values = [
            'change_password_form[plainPassword][first]' => 'aaaaaaaaa',
            'change_password_form[plainPassword][second]' => 'aaaaaaaaa',
        ];

        yield 'Should return an error if password contains only lowers' => [
            self::URL,
            'app_update_password',
            'submit',
            $values,
            [
                [
                    'message' => 'password_help_criteria',
                    'params' => null,
                ],
                [
                    'message' => 'password_criterion_uppercase',
                    'params' => null,
                ],
                [
                    'message' => 'password_criterion_nonAlphabetic',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
        ];
    }

    /** @dataProvider provideTestRoute */
    public function testRoute(
        string $url,
        int $expectedStatusCode,
        ?string $userMail = null,
        ?string $expectedRedirect = null,
        string $method = 'GET',
        bool $isXmlHttpRequest = false,
        array $body = [],
    ): void {
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    /**  @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    /**
     * @param array<string, string>         $values
     * @param array<array<string, ?string>> $errors
     *
     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email);
    }

    public function testPasswordIsUpdated(): void
    {
        self::ensureKernelShutdown();
        $today = (new \DateTime())->format('Y-m-d');
        $client = static::createClient();
        $user = $this->getTestUserFromDb(MemberFixture::MEMBER_MAIL);
        $client->loginUser($user);
        self::assertTrue($this->hasher->isPasswordValid($user, UserFactory::STRONG_PASSWORD_CLEAR));
        self::assertFalse($today === $user->getPasswordUpdatedAt()->format('Y-m-d'));

        $crawler = $client->request('GET', self::URL);
        $form = $crawler->selectButton('Valider')->form();
        $form->setValues([
            'change_password_form[plainPassword][first]' => 'NewPassword1!',
            'change_password_form[plainPassword][second]' => 'NewPassword1!',
        ]);
        $client->submit($form);

        $user = $this->getTestUserFromDb(MemberFixture::MEMBER_MAIL);
        self::assertFalse($this->hasher->isPasswordValid($user, UserFactory::STRONG_PASSWORD_CLEAR));
        self::assertTrue($this->hasher->isPasswordValid($user, 'NewPassword1!'));
        self::assertTrue($today === $user->getPasswordUpdatedAt()->format('Y-m-d'));
    }
}
