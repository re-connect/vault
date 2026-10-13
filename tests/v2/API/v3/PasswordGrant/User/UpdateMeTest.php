<?php

namespace App\Tests\v2\API\v3\PasswordGrant\User;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\ResponseInterface;

class UpdateMeTest extends AbstractPasswordGrantApiTestCase
{
    public function testBeneficiaryUpdatesProfile(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT);

        $this->updateMe(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, [
            'prenom' => 'Jeanne',
            'nom' => 'Martin',
            'email' => 'jeanne.martin@mail.com',
            'telephone' => '0611223344',
            'birth_date' => '1990-01-31',
            'question_secrete' => 'Quel est le nom de votre premier animal ?',
            'reponse_secrete' => 'Rex',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'prenom' => 'Jeanne',
            'nom' => 'Martin',
            'email' => 'jeanne.martin@mail.com',
            'telephone' => '+33611223344',
            'beneficiary' => ['question_secrete' => 'Quel est le nom de votre premier animal ?', 'reponse_secrete' => 'Rex'],
        ]);
        $user = $this->findUser($user->getId());
        $this->assertSame('1990-01-31', $user->getSubjectBeneficiaire()->getDateNaissance()->format('Y-m-d'));
        $this->assertStringStartsWith('jeanne.martin.31/01/1990', $user->getUsername());
    }

    public function testOnlyUpdatesFieldsSent(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT);
        $previousNom = $user->getNom();
        $previousQuestion = $user->getSubjectBeneficiaire()->getQuestionSecrete();

        $this->updateMe(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, ['prenom' => 'Jeanne']);

        $this->assertResponseIsSuccessful();
        $user = $this->findUser($user->getId());
        $this->assertSame('Jeanne', $user->getPrenom());
        $this->assertSame($previousNom, $user->getNom());
        $this->assertSame($previousQuestion, $user->getSubjectBeneficiaire()->getQuestionSecrete());
    }

    public function testInvalidProfileIsNotSaved(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT);
        $previousEmail = $user->getEmail();

        $response = $this->updateMe(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, ['email' => 'not an email', 'reponse_secrete' => '']);

        $this->assertResponseStatusCodeSame(422);
        // Same format as the other v3 validation errors
        $propertyPaths = array_column($response->toArray(false)['violations'], 'propertyPath');
        $this->assertContains('email', $propertyPaths);
        $this->assertContains('reponse_secrete', $propertyPaths);
        $this->assertSame($previousEmail, $this->findUser($user->getId())->getEmail());
    }

    public function testInvalidBirthDateFormatIsRejected(): void
    {
        $this->updateMe(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, ['birth_date' => '31/01/1990']);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testMemberUpdatesProfile(): void
    {
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL);

        $this->updateMe(MemberFixture::MEMBER_MAIL, ['prenom' => 'Paul', 'nom' => 'Durand', 'email' => MemberFixture::MEMBER_MAIL]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['prenom' => 'Paul', 'nom' => 'Durand']);
        $this->assertSame('Durand', $this->findUser($user->getId())->getNom());
    }

    public function testMemberCanNotChangeTheirEmail(): void
    {
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL);

        $this->updateMe(MemberFixture::MEMBER_MAIL, ['email' => 'new.email@mail.com']);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(MemberFixture::MEMBER_MAIL, $this->findUser($user->getId())->getEmail());
    }

    /** @param array<string, string> $body */
    private function updateMe(string $email, array $body): ResponseInterface
    {
        return $this->requestAsUser($email, Request::METHOD_PATCH, '/users/me', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $body,
        ]);
    }

    private function findUser(int $id): User
    {
        $this->em->clear();

        return $this->em->find(User::class, $id);
    }
}
