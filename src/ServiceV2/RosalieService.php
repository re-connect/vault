<?php

namespace App\ServiceV2;

use App\Entity\Beneficiaire;
use App\Entity\Helper\BeneficiaryCheckOnRosalie;
use App\Repository\BeneficiaireRepository;
use App\Repository\ClientBeneficiaireRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
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
        private readonly ClientBeneficiaireRepository $clientBeneficiaireRepository,
    ) {
    }

    public function checkBeneficiaryOnRosalie(Beneficiaire $beneficiary): BeneficiaryCheckOnRosalie
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

            return BeneficiaryCheckOnRosalie::fromResponse($beneficiary, $response->getStatusCode(), $response->getContent(false));
        } catch (ExceptionInterface) {
            return new BeneficiaryCheckOnRosalie($beneficiary);
        }
    }

    public function linkBeneficiaryToRosalie(Beneficiaire $beneficiary): bool
    {
        $siSiaoNumber = $beneficiary->getSiSiaoNumber();
        try {
            if (!$this->clientBeneficiaireRepository->findOneByDistantIdAndClientName($siSiaoNumber, 'rosalie')) {
                $this->clientLinkService->linkBeneficiaryToClientWithName($beneficiary, 'rosalie', $siSiaoNumber);
                $this->em->flush();

                return true;
            }
        } catch (NonUniqueResultException) {
            return false;
        }

        return false;
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
