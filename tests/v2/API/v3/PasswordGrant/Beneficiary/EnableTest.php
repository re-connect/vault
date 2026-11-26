<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Beneficiary;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EnableTest extends AbstractPasswordGrantApiTestCase
{
    private const string NEW_PASSWORD = 'NewStrongPassword1!';

    public function testBeneficiaryEnablesAccount(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT);

        $this->enable(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, [
            'secret_question' => 'Quel est le nom de votre premier animal ?',
            'secret_answer' => 'Rex',
            'password' => self::NEW_PASSWORD,
            'email' => 'enabled.beneficiary@mail.com',
        ]);

        $this->assertResponseStatusCodeSame(204);
        $user = $this->findUser($user->getId());
        $this->assertSame('Quel est le nom de votre premier animal ?', $user->getSubjectBeneficiaire()->getQuestionSecrete());
        $this->assertSame('Rex', $user->getSubjectBeneficiaire()->getReponseSecrete());
        $this->assertSame('enabled.beneficiary@mail.com', $user->getEmail());
        $this->assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, self::NEW_PASSWORD));
    }

    public function testOtherSecretQuestionReplacesTheChosenOne(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT);

        $this->enable(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, [
            'secret_question' => 'Autre',
            'other_secret_question' => 'Ma question',
            'secret_answer' => 'Ma réponse',
            'password' => self::NEW_PASSWORD,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('Ma question', $this->findUser($user->getId())->getSubjectBeneficiaire()->getQuestionSecrete());
    }

    public function provideInvalidPayloads(): \Generator
    {
        yield 'Weak password' => [['secret_question' => 'Question ?', 'secret_answer' => 'Rex', 'password' => 'password']];
        yield 'Missing secret question' => [['secret_answer' => 'Rex', 'password' => self::NEW_PASSWORD]];
        yield 'Other secret question without its text' => [['secret_question' => 'Autre', 'secret_answer' => 'Rex', 'password' => self::NEW_PASSWORD]];
        yield 'Missing secret answer' => [['secret_question' => 'Question ?', 'password' => self::NEW_PASSWORD]];
        yield 'Invalid email' => [['secret_question' => 'Question ?', 'secret_answer' => 'Rex', 'password' => self::NEW_PASSWORD, 'email' => 'not an email']];
        yield 'Email already used' => [['secret_question' => 'Question ?', 'secret_answer' => 'Rex', 'password' => self::NEW_PASSWORD, 'email' => BeneficiaryFixture::BENEFICIARY_MAIL]];
    }

    /**
     * @dataProvider provideInvalidPayloads
     *
     * @param array<string, string> $payload
     */
    public function testInvalidPayloadDoesNotEnableAccount(array $payload): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT);
        $previousPassword = $user->getPassword();
        $previousQuestion = $user->getSubjectBeneficiaire()->getQuestionSecrete();

        $this->enable(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, $payload);

        $this->assertResponseStatusCodeSame(422);
        $user = $this->findUser($user->getId());
        $this->assertSame($previousPassword, $user->getPassword());
        $this->assertSame($previousQuestion, $user->getSubjectBeneficiaire()->getQuestionSecrete());
    }

    public function testMemberCanNotEnableAccount(): void
    {
        $this->enable(MemberFixture::MEMBER_WITH_CLIENT, ['secret_question' => 'Question ?', 'secret_answer' => 'Rex', 'password' => self::NEW_PASSWORD]);

        $this->assertResponseStatusCodeSame(403);
    }

    /** @param array<string, string> $payload */
    private function enable(string $email, array $payload): ResponseInterface
    {
        return $this->requestAsUser($email, Request::METHOD_PUT, '/beneficiaries/me/enable', ['json' => $payload]);
    }

    private function findUser(int $id): User
    {
        $this->em->clear();

        return $this->em->find(User::class, $id);
    }
}
