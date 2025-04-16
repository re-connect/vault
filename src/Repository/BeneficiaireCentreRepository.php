<?php

namespace App\Repository;

use App\Entity\Attributes\BeneficiaireCentre;
use Doctrine\ORM\EntityRepository;

/**
 * BeneficiaireCentreRepository.
 *
 * @method BeneficiaireCentre|null find($id, $lockMode = null, $lockVersion = null)
 * @method BeneficiaireCentre|null findOneBy(array $criteria, array $orderBy = null)
 * @method BeneficiaireCentre[]    findAll()
 * @method BeneficiaireCentre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BeneficiaireCentreRepository extends EntityRepository
{
}
