<?php

namespace App\Tests\v2\API\v2\PasswordGrant\User;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\v2\PasswordGrant\AbstractPasswordGrantApiV2TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserTest extends AbstractPasswordGrantApiV2TestCase
{
    private const string NEW_PASSWORD = 'NewStrongPassword1!';

    public function provideUserMails(): \Generator
    {
        yield 'Beneficiary' => [BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Member' => [MemberFixture::MEMBER_WITH_CLIENT];
    }

    /** @dataProvider provideUserMails */
    public function testGetMe(string $email): void
    {
        $user = UserFactory::findByEmail($email);

        $this->requestAsUser($email, Request::METHOD_GET, '/user');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $user->getId(),
            'email' => $email,
            'type_user' => $user->getTypeUser(),
            'subject_id' => $user->getSubject()->getId(),
        ]);
    }

    public function testBeneficiaryUpdatesProfile(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, Request::METHOD_PUT, sprintf('/users/%d', $user->getId()), ['json' => [
            'prenom' => 'Jeanne',
            'nom' => 'Martin',
            'email' => 'jeanne.martin@mail.com',
            'telephone' => '0611223344',
            'date_naissance' => '1990-01-31',
            'question_secrete' => 'Quel est le nom de votre premier animal ?',
            'reponse_secrete' => 'Rex',
        ]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $user->getId(),
            'prenom' => 'Jeanne',
            'nom' => 'Martin',
            'email' => 'jeanne.martin@mail.com',
            'telephone' => '0611223344',
        ]);

        $updatedUser = $this->findUser($user->getId());
        $this->assertSame('1990-01-31', $updatedUser->getSubjectBeneficiaire()->getDateNaissance()->format('Y-m-d'));
        $this->assertSame('Quel est le nom de votre premier animal ?', $updatedUser->getSubjectBeneficiaire()->getQuestionSecrete());
    }

    public function testMemberUpdatesProfile(): void
    {
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_WITH_CLIENT);

        $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_PUT, sprintf('/users/%d', $user->getId()), ['json' => [
            'prenom' => 'Paul',
            'nom' => 'Durand',
            'email' => 'paul.durand@mail.com',
            'telephone' => '0611223344',
        ]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['id' => $user->getId(), 'prenom' => 'Paul', 'nom' => 'Durand', 'email' => 'paul.durand@mail.com']);
    }

    public function testCanNotUpdateAnotherUserProfile(): void
    {
        $otherUser = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, Request::METHOD_PUT, sprintf('/users/%d', $otherUser->getId()), ['json' => [
            'prenom' => 'Jeanne',
            'nom' => 'Martin',
        ]]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdatePassword(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, Request::METHOD_PATCH, '/user/password', ['json' => [
            'password' => self::NEW_PASSWORD,
        ]]);

        $this->assertResponseIsSuccessful();
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($hasher->isPasswordValid($this->findUser($user->getId()), self::NEW_PASSWORD));
    }

    public function provideInvalidPasswords(): \Generator
    {
        yield 'Missing password' => [[], ['password' => 'missing']];
        yield 'Weak password' => [['password' => 'password'], ['password' => 'weak']];
    }

    /**
     * @dataProvider provideInvalidPasswords
     *
     * @param array<string, string> $body
     * @param array<string, string> $expectedJson
     */
    public function testCanNotUpdateWithInvalidPassword(array $body, array $expectedJson): void
    {
        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, Request::METHOD_PATCH, '/user/password', ['json' => $body]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonEquals($expectedJson);
    }

    private function findUser(int $id): User
    {
        $this->em->clear();

        return $this->em->find(User::class, $id);
    }
}
