<?php

namespace App\Tests\v2\API\v2\Beneficiary;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\ConsultationBeneficiaire;
use App\Entity\Attributes\Membre;
use App\Tests\v2\API\v2\AbstractApiV2Test;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use Symfony\Component\HttpFoundation\Request;

class BeneficiaryConsultationTest extends AbstractApiV2Test
{
    public function testRecordBeneficiaryConsultation(): void
    {
        $client = $this->em->getRepository(Client::class)->findOneBy(['name' => 'applimobile']);
        $pro = $this->em->getRepository(Membre::class)->findByClientIdentifier($client->getIdentifier())[0];
        $beneficiary = $this->em->getRepository(Beneficiaire::class)->findByClientIdentifier($client->getIdentifier())[0];

        $this->loginAsMember();
        $beneficiaryConsultationRepository = $this->em->getRepository(ConsultationBeneficiaire::class);
        $consultationsBeforeTestCount = count($beneficiaryConsultationRepository->findAll());

        $this->client->request(Request::METHOD_GET, $this->generateUrl(sprintf('/beneficiaries/%s/documents', $beneficiary->getId())));

        $consultationsAfterTest = $beneficiaryConsultationRepository->findAll();
        $consultationAfterTestCount = count($consultationsAfterTest);
        $lastConsultation = end($consultationsAfterTest);
        self::assertEquals($consultationsBeforeTestCount + 1, $consultationAfterTestCount);
        self::assertEquals($lastConsultation->getBeneficiaire()->getId(), $beneficiary->getId());
        self::assertEquals($lastConsultation->getMembre()->getId(), $pro->getId());
    }
}
