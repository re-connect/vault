<?php

namespace App\Tests\v2\Controller\BeneficiaryConsultation;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Attributes\ConsultationBeneficiaire;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Zenstruck\Foundry\Test\Factories;

class BeneficiaryConsultationTest extends AbstractControllerTest
{
    use Factories;

    /** @dataProvider provideTestBeneficiaryConsultationRecord */
    public function testBeneficiaryConsultationRecord(bool $shouldRecord, string $url): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $proUser = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES)->object();
        $client->loginUser($proUser);
        $repository = $this->getEntityManager()->getRepository(ConsultationBeneficiaire::class);

        $consultationBeforeTestCount = count($repository->findAll());

        $client->request('GET', sprintf($url, $beneficiary->getId()));

        $consultationAfterTest = $repository->findAll();
        $consultationAfterTestCount = count($consultationAfterTest);

        if ($shouldRecord) {
            $lastConsultation = end($consultationAfterTest);
            self::assertEquals($consultationAfterTestCount, $consultationBeforeTestCount + 1);
            self::assertEquals($lastConsultation->getBeneficiaire()->getId(), $beneficiary->getId());
            self::assertEquals($lastConsultation->getMembre()->getId(), $proUser->getSubject()->getId());
        } else {
            self::assertEquals($consultationBeforeTestCount, $consultationAfterTestCount);
        }
    }

    public function provideTestBeneficiaryConsultationRecord(): \Generator
    {
        yield 'Should record beneficiary consultation on events list' => [true, '/beneficiary/%d/events'];
        yield 'Should record beneficiary consultation on contact list' => [true, '/beneficiary/%d/contacts'];
        yield 'Should record beneficiary consultation on note list' => [true, '/beneficiary/%d/notes'];
        yield 'Should record beneficiary consultation on document list' => [true, '/beneficiary/%d/documents'];
        yield 'Should not record beneficiary consultation on event form' => [false, '/beneficiary/%s/events/create'];
        yield 'Should not record beneficiary consultation on contact form' => [false, '/beneficiary/%s/contacts/create'];
        yield 'Should not record beneficiary consultation on note form' => [false, '/beneficiary/%s/notes/create'];
    }
}
