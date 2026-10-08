<?php

namespace App\Tests\v2\API\v2\PasswordGrant\Beneficiary;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\DataFixtures\v2\RelayFixture;
use App\Entity\Beneficiaire;
use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\v2\PasswordGrant\AbstractPasswordGrantApiV2TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MobileBeneficiaryTest extends AbstractPasswordGrantApiV2TestCase
{
    private const string NEW_PASSWORD = 'NewStrongPassword1!';

    public function testMemberListsBeneficiaries(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);

        $response = $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_GET, '/beneficiaires');

        $this->assertResponseIsSuccessful();
        $this->assertContains($beneficiary->getId(), array_column($response->toArray(), 'id'));
    }

    public function testBeneficiaryCanNotListBeneficiaries(): void
    {
        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_GET, '/beneficiaires');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testMemberCreatesBeneficiary(): void
    {
        $relay = RelayFactory::find(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1]);

        $response = $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_POST, '/beneficiaries', ['json' => [
            'first_name' => 'Jean',
            'last_name' => 'Mobile',
            'phone' => '0611223344',
            'email' => 'jean.mobile@mail.com',
            'password' => self::NEW_PASSWORD,
            'confirmPassword' => self::NEW_PASSWORD,
            'secret_question' => 'Quel est le nom de votre premier animal ?',
            'secret_question_answer' => 'Rex',
            'birth_date' => '31/01/1990',
            'centers' => [$relay->getId()],
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['prenom' => 'Jean', 'nom' => 'Mobile', 'email' => 'jean.mobile@mail.com']);

        $this->em->clear();
        $beneficiary = $this->em->find(Beneficiaire::class, $response->toArray()['id']);
        $this->assertSame('31/01/1990', $beneficiary->getDateNaissance()->format('d/m/Y'));
        $this->assertSame([$relay->getId()], array_map(fn ($relay) => $relay->getId(), $beneficiary->getCentres()->toArray()));
    }

    public function testCanNotCreateBeneficiaryWithInvalidBirthDate(): void
    {
        $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_POST, '/beneficiaries', ['json' => [
            'first_name' => 'Jean',
            'last_name' => 'Mobile',
            'password' => self::NEW_PASSWORD,
            'birth_date' => '1990-01-31',
        ]]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testBeneficiaryCanNotCreateBeneficiary(): void
    {
        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_POST, '/beneficiaries', ['json' => [
            'first_name' => 'Jean',
            'last_name' => 'Mobile',
            'birth_date' => '31/01/1990',
        ]]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testBeneficiaryDeletesOwnAccount(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_DELETE);
        $beneficiaryId = $beneficiary->getId();
        $userId = $beneficiary->getUser()->getId();

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_DELETE, Request::METHOD_DELETE, sprintf('/beneficiaries/%d', $beneficiaryId));

        $this->assertResponseStatusCodeSame(204);
        $this->em->clear();
        $this->assertNull($this->em->find(Beneficiaire::class, $beneficiaryId));
        $this->assertNull($this->em->find(User::class, $userId));
    }

    public function testCanNotDeleteAnotherBeneficiary(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_DELETE, Request::METHOD_DELETE, sprintf('/beneficiaries/%d', $beneficiary->getId()));

        $this->assertResponseStatusCodeSame(403);
        $this->em->clear();
        $this->assertNotNull($this->em->find(Beneficiaire::class, $beneficiary->getId()));
    }

    public function testGetSecretQuestions(): void
    {
        $response = $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_GET, '/get-secret-questions');

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($response->toArray());
    }

    public function testBeneficiaryEnablesAccount(): void
    {
        $user = UserFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT);

        $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, Request::METHOD_PATCH, '/beneficiary/enable', ['json' => [
            'question_secrete' => 'Autre',
            'autre_question_secrete' => 'Ma question secrète',
            'reponse_secrete' => 'Ma réponse',
            'password' => self::NEW_PASSWORD,
            'confirmPassword' => self::NEW_PASSWORD,
            'email' => 'enabled.beneficiary@mail.com',
        ]]);

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $user = $this->em->find(User::class, $user->getId());
        $this->assertSame('Ma question secrète', $user->getSubjectBeneficiaire()->getQuestionSecrete());
        $this->assertSame('enabled.beneficiary@mail.com', $user->getEmail());
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($hasher->isPasswordValid($user, self::NEW_PASSWORD));
    }

    public function testCanNotEnableAccountWithoutOtherSecretQuestion(): void
    {
        $response = $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_EDIT, Request::METHOD_PATCH, '/beneficiary/enable', ['json' => [
            'question_secrete' => 'Autre',
            'reponse_secrete' => 'Ma réponse',
            'password' => self::NEW_PASSWORD,
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('autreQuestionSecrete', $response->toArray(false));
    }

    public function testMemberCanNotEnableAccount(): void
    {
        $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_PATCH, '/beneficiary/enable', ['json' => [
            'question_secrete' => 'Autre',
            'autre_question_secrete' => 'Ma question secrète',
            'reponse_secrete' => 'Ma réponse',
            'password' => self::NEW_PASSWORD,
        ]]);

        $this->assertResponseStatusCodeSame(403);
    }
}
