<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\BeneficiaryDto;
use App\Entity\Beneficiaire;
use Doctrine\ORM\EntityManagerInterface;

class BeneficiaryProcessor implements ProcessorInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /** @param BeneficiaryDto $data */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Beneficiaire
    {
        $beneficiary = $data->toBeneficiary();
        $this->em->persist($beneficiary);
        $this->em->flush();

        return $beneficiary;
    }
}
