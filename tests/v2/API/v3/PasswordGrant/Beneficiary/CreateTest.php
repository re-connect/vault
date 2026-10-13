<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Beneficiary;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\DataFixtures\v2\RelayFixture;
use App\Entity\Beneficiaire;
use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CreateTest extends AbstractPasswordGrantApiTestCase
{
    public function testProCreatesBeneficiaryInManagedRelay(): void
    {
        $relay = RelayFactory::find(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1]);
        $pro = UserFactory::findByEmail(MemberFixture::MEMBER_WITH_CLIENT);

        $response = $this->create(MemberFixture::MEMBER_WITH_CLIENT, ['centers' => [(string) $relay->getId()]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['user' => ['prenom' => 'Jean', 'nom' => 'Mobile', 'email' => 'jean.mobile@mail.com']]);
        $this->assertNotEmpty($response->toArray()['user']['username']);

        $this->em->clear();
        $beneficiary = $this->em->find(Beneficiaire::class, $response->toArray()['id']);
        $this->assertSame('1990-01-31', $beneficiary->getDateNaissance()->format('Y-m-d'));
        $relayLinks = $beneficiary->getBeneficiairesCentres()->toArray();
        $this->assertCount(1, $relayLinks);
        $this->assertSame($relay->getId(), $relayLinks[0]->getCentre()->getId());
        $this->assertTrue($relayLinks[0]->getBValid());

        // The pro can then see the beneficiary
        $ids = array_column($this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_GET, '/beneficiaries')->toArray()['hydra:member'], 'id');
        $this->assertContains($beneficiary->getId(), $ids);

        // The pro and their relay are recorded as the creators
        $this->em->clear();
        $user = $this->em->find(Beneficiaire::class, $beneficiary->getId())->getUser();
        $this->assertSame($pro->getId(), $user->getCreatorUser()?->getEntity()?->getId());
        $this->assertSame($relay->getId(), $user->getCreatorCentreRelay()?->getId());
    }

    public function provideInvalidRelays(): \Generator
    {
        yield 'No relay' => [fn () => []];
        yield 'Relay the pro does not manage' => [fn () => [(string) RelayFactory::find(['nom' => RelayFixture::DEFAULT_PRO_RELAY])->getId()]];
        yield 'Unknown relay' => [fn () => ['999999999']];
    }

    /** @dataProvider provideInvalidRelays */
    public function testProMustCreateBeneficiaryInManagedRelay(\Closure $relayIds): void
    {
        $this->create(MemberFixture::MEMBER_WITH_CLIENT, ['centers' => $relayIds()]);

        $this->assertResponseStatusCodeSame(422);
        $this->em->clear();
        $this->assertNull(UserFactory::repository()->findOneBy(['email' => 'jean.mobile@mail.com']));
    }

    public function testBeneficiaryCanNotCreateBeneficiary(): void
    {
        $relay = RelayFactory::find(['nom' => RelayFixture::SHARED_PRO_BENEFICIARY_RELAY_1]);

        $this->create(BeneficiaryFixture::BENEFICIARY_MAIL, ['centers' => [(string) $relay->getId()]]);

        $this->assertResponseStatusCodeSame(403);
    }

    /** @param array<string, mixed> $payload */
    private function create(string $email, array $payload): ResponseInterface
    {
        return $this->requestAsUser($email, Request::METHOD_POST, '/beneficiaries', ['json' => [
            'first_name' => 'Jean',
            'last_name' => 'Mobile',
            'birth_date' => '1990-01-31',
            'email' => 'jean.mobile@mail.com',
            'phone' => '0611223344',
            'password' => 'NewStrongPassword1!',
            'secret_question' => 'Quel est le nom de votre premier animal ?',
            'secret_question_answer' => 'Rex',
            ...$payload,
        ]]);
    }
}
