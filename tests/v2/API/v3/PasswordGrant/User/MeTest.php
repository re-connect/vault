<?php

namespace App\Tests\v2\API\v3\PasswordGrant\User;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class MeTest extends AbstractPasswordGrantApiTestCase
{
    public function testBeneficiaryGetsOwnProfile(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $user = $beneficiary->getUser();

        $response = $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_GET, '/users/me');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => BeneficiaryFixture::BENEFICIARY_MAIL,
            'prenom' => $user->getPrenom(),
            'nom' => $user->getNom(),
            'type_user' => User::USER_TYPE_BENEFICIAIRE,
            'subject_id' => $beneficiary->getId(),
            'beneficiary' => [
                'question_secrete' => $beneficiary->getQuestionSecrete(),
                'reponse_secrete' => $beneficiary->getReponseSecrete(),
                'total_file_size' => $beneficiary->getTotalFileSize(),
            ],
        ]);
        $this->assertArrayNotHasKey('password', $response->toArray());
    }

    public function testMemberGetsOwnProfile(): void
    {
        $member = MembreFactory::findByEmail(MemberFixture::MEMBER_WITH_CLIENT);

        $response = $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_GET, '/users/me');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $member->getUser()->getId(),
            'type_user' => User::USER_TYPE_MEMBRE,
            'subject_id' => $member->getId(),
        ]);
        $this->assertArrayNotHasKey('beneficiary', $response->toArray());
    }

    public function testRequiresAuthentication(): void
    {
        static::createClient()->request(Request::METHOD_GET, '/api/v3/users/me');

        $this->assertResponseStatusCodeSame(401);
    }
}
