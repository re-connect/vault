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
    private const KEY_VERIFICATION_PATH = '/famille/verification_cle/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $em,
        private readonly string $rosalieBaseUrl,
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
                $this->rosalieBaseUrl.self::KEY_VERIFICATION_PATH,
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
        $this->clientLinkService->linkBeneficiaryToClientWithName($beneficiary, 'rosalie', $beneficiary->getSiSiaoNumber());
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
