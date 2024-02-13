<?php

namespace App\Tests\v1\Controller\Rest;

use App\Entity\Beneficiaire;
use App\Entity\ConsultationBeneficiaire;
use App\Entity\Membre;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use Symfony\Component\HttpFoundation\Request;

class BeneficiaryConsultationTest extends AbstractControllerTest
{
    public function testRecordBeneficiaryConsultation(): void
    {
        $em = $this->getEntityManager();
        $client = $em->getRepository(Client::class)->findOneBy(['name' => 'applimobile']);
        $pro = $em->getRepository(Membre::class)->findByClientIdentifier($client->getIdentifier())[0];
        $beneficiary = $em->getRepository(Beneficiaire::class)->findByClientIdentifier($client->getIdentifier())[0];

        $this->loginAsMember();
        $beneficiaryConsultationRepository = $em->getRepository(ConsultationBeneficiaire::class);
        $consultationsBeforeTestCount = count($beneficiaryConsultationRepository->findAll());

        $this->client->request(Request::METHOD_GET, $this->generateUrl(sprintf('beneficiaries/%s/documents', $beneficiary->getId())));

        $consultationsAfterTest = $beneficiaryConsultationRepository->findAll();
        $consultationAfterTestCount = count($consultationsAfterTest);
        $lastConsultation = end($consultationsAfterTest);
        self::assertEquals($consultationAfterTestCount, $consultationsBeforeTestCount + 1);
        self::assertEquals($lastConsultation->getBeneficiaire()->getId(), $beneficiary->getId());
        self::assertEquals($lastConsultation->getMembre()->getId(), $pro->getId());
    }
}
