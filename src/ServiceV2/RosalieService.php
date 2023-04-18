<?php

namespace App\ServiceV2;

use App\Entity\Beneficiaire;
use App\Repository\BeneficiaireRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RosalieService
{
    public const BASE_URL = 'https://test.ssp-online.fr/api';
    public const NUMBER_CHECK_ENDPOINT = self::BASE_URL.'/famille/verification_cle/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $em,
        private readonly string $rosalieBasicToken,
        private readonly BeneficiaryClientLinkService $clientLinkService,
        private readonly BeneficiaireRepository $beneficiaireRepository,
        private readonly ClientRepository $clientRepository,
    ) {
    }

    public function beneficiaryExistsOnRosalie(Beneficiaire $beneficiary): bool
    {
        try {
            $response = $this->httpClient->request(
                Request::METHOD_POST,
                self::NUMBER_CHECK_ENDPOINT,
                [
                    'headers' => [sprintf('Authorization: Basic %s', $this->rosalieBasicToken)],
                    'body' => [
                        'cle' => $beneficiary->getSiSiaoNumber(),
                        'nom' => $beneficiary->getLastName(),
                        'prenom' => $beneficiary->getFirstName(),
                        'naissance' => $beneficiary->getDateNaissanceStr(),
                    ],
                ],
            );

            return 300 > $response->getStatusCode();
        } catch (ExceptionInterface) {
            return false;
        }
    }

    public function linkBeneficiaryToRosalie(Beneficiaire $beneficiary): void
    {
        $relay = $beneficiary->getCentres()->count() > 0 ? $beneficiary->getCentres()->first() : null;
        $this->clientLinkService->linkBeneficiaryToClientWithName($beneficiary, 'rosalie', $beneficiary->getSiSiaoNumber(), $relay);
        $this->em->flush();
    }

    public function migrateIdToSiSiaoId(int $id, string $number): void
    {
        $rosalieClient = $this->clientRepository->findOneBy(['nom' => 'rosalie']);
        $this->beneficiaireRepository->findByDistantId($id, $rosalieClient->getRandomId())
            ?->setSiSiaoNumber($number)
            ->getExternalLinkForClient($rosalieClient)->setDistantId($number);

        $this->em->flush();
    }
}
