<?php

namespace App\Tests\v2\API\v3\ClientCredentials\Beneficiary;

use App\Entity\ClientCentre;
use App\Entity\User;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\v2\API\AbstractClientCredentialsApiTestCase;
use Doctrine\ORM\EntityManagerInterface;

class CreateInRelaysTest extends AbstractClientCredentialsApiTestCase
{
    private const string EMAIL = 'relay.check@test.com';

    public function testClientCreatesBeneficiaryInItsOwnRelay(): void
    {
        $relay = RelayFactory::createOne();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $externalLink = new ClientCentre(ClientFactory::find(['nom' => 'rosalie']), 'rosalie-relay');
        $relay->addExternalLink($externalLink);
        $em->persist($externalLink);
        $em->flush();

        $this->assertEndpoint('rosalie', '/beneficiaries', 'POST', 201, null, $this->getPayload($relay->getId()));

        $em->clear();
        $userRelays = $em->getRepository(User::class)->findOneBy(['email' => self::EMAIL])->getSubjectBeneficiaire()->getBeneficiairesCentres();
        $this->assertCount(1, $userRelays);
        $this->assertSame($relay->getId(), $userRelays->first()->getCentre()->getId());
    }

    public function testClientCanNotCreateBeneficiaryInAnotherRelay(): void
    {
        $relay = RelayFactory::createOne();

        $this->assertEndpoint('rosalie', '/beneficiaries', 'POST', 422, null, $this->getPayload($relay->getId()));

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $this->assertNull($em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]));
    }

    /** @return array<string, mixed> */
    private function getPayload(int $relayId): array
    {
        return [
            'last_name' => 'check',
            'first_name' => 'relay',
            'birth_date' => '1990-01-31',
            'email' => self::EMAIL,
            'distant_id' => 'relay-check',
            'password' => 'P@ssw0rd!',
            'centers' => [(string) $relayId],
        ];
    }
}
