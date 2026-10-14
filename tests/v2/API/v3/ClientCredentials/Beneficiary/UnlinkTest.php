    <?php

namespace App\Tests\v2\API\v3\ClientCredentials\Beneficiary;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Beneficiaire;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use App\Tests\v2\API\AbstractClientCredentialsApiTestCase;
use Doctrine\ORM\EntityManagerInterface;

class UnlinkTest extends AbstractClientCredentialsApiTestCase
{
    public function testUnlinkBeneficiaryWithUpdateScope(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK);

        $this->assertEndpoint('read_and_update_client', sprintf('/beneficiaries/%d/unlink', $beneficiary->getId()), 'PATCH', 200, null, []);

        $this->assertFalse($this->hasLinkForClient($beneficiary->getId(), 'read_and_update_client'));
    }

    public function provideClientsWithoutUpdateScope(): \Generator
    {
        yield 'No scopes' => ['no_scopes_client'];
        yield 'Read only scopes' => ['read_only_client'];
    }

    /** @dataProvider provideClientsWithoutUpdateScope */
    public function testCanNotUnlinkBeneficiaryWithoutUpdateScope(string $clientName): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_WITH_CLIENT_LINK);

        $this->assertEndpoint($clientName, sprintf('/beneficiaries/%d/unlink', $beneficiary->getId()), 'PATCH', 403, null, []);

        $this->assertTrue($this->hasLinkForClient($beneficiary->getId(), $clientName));
    }

    private function hasLinkForClient(int $beneficiaryId, string $clientName): bool
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->find(Beneficiaire::class, $beneficiaryId)->hasExternalLinkForClient(ClientFactory::find(['nom' => $clientName]));
    }
}
