<?php

namespace App\Tests\v2\API\v3\PasswordGrant\Beneficiary;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class BeneficiaryCollectionTest extends AbstractPasswordGrantApiTestCase
{
    public function testMemberListsManagedBeneficiaries(): void
    {
        $managedBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $notManagedBeneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS);

        $response = $this->requestAsUser(MemberFixture::MEMBER_WITH_CLIENT, Request::METHOD_GET, '/beneficiaries');

        $this->assertResponseIsSuccessful();
        $ids = array_column($response->toArray()['hydra:member'], 'id');
        $this->assertContains($managedBeneficiary->getId(), $ids);
        $this->assertNotContains($notManagedBeneficiary->getId(), $ids);
    }

    public function testBeneficiaryCanNotListBeneficiaries(): void
    {
        $response = $this->requestAsUser(BeneficiaryFixture::BENEFICIARY_MAIL, Request::METHOD_GET, '/beneficiaries');

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $response->toArray()['hydra:member']);
    }
}
