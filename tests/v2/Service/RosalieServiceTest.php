<?php

namespace App\Tests\v2\Service;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\User;
use App\Repository\BeneficiaireRepository;
use App\Repository\ClientRepository;
use App\ServiceV2\BeneficiaryClientLinkService;
use App\ServiceV2\RosalieService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RosalieServiceTest extends KernelTestCase
{
    private ?RosalieService $service = null;
    private ?MockObject $httpClient = null;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $beneficiaryClientService = $this->createMock(BeneficiaryClientLinkService::class);
        $beneficiaryRepository = $this->createMock(BeneficiaireRepository::class);
        $clientRepository = $this->createMock(ClientRepository::class);
        $this->service = new RosalieService($this->httpClient, $em, 'http://rosalie', 'token', $beneficiaryClientService, $beneficiaryRepository, $clientRepository);
    }

    public function testSiSiaoNumberExistsOnRosalie(): void
    {
        $this->httpClient->expects($this->once())->method('request')
            ->with(
                Request::METHOD_POST,
                'http://rosalie/famille/verification_cle/',
                [
                    'headers' => ['Authorization: Basic token'],
                    'body' => [
                        'cle' => 'SI-00000000',
                        'nom' => 'BAGGINS',
                        'prenom' => 'Frodo',
                        'naissance' => '01/01/1975',
                    ],
                ],
            );
        $beneficiary = (new Beneficiaire())
            ->addCentre(new Centre())
            ->setSiSiaoNumber('SI-00000000')
            ->setUser((new User())->setPrenom('Frodo')->setNom('BAGGINS'))
            ->setDateNaissance(\DateTime::createFromFormat('d/m/Y', '01/01/1975'));
        $this->service->beneficiaryExistsOnRosalie($beneficiary);
    }
}
