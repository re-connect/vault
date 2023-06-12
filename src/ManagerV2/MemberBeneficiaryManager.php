<?php

namespace App\ManagerV2;

use App\Entity\Beneficiaire;
use App\Entity\ConsultationBeneficiaire;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class MemberBeneficiaryManager
{
    use UserAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security               $security,
    )
    {
    }

    public function handleFirstMemberVisit(Beneficiaire $beneficiary): void
    {
        $user = $this->getUser();
        if (null !== $user && $user->isMembre()) {
            $firstMemberVisit = (new ConsultationBeneficiaire())->setBeneficiaire($beneficiary)->setMembre($user->getSubjectMembre());

            $this->em->persist($firstMemberVisit);
            $this->em->flush();
        }
    }

    public function isFirstMemberVisit(Beneficiaire $beneficiary): bool
    {
        $user = $this->getUser();
        if (null !== $user && $user->isMembre()) {
            $query = $this->em->getRepository(ConsultationBeneficiaire::class)
                ->createQueryBuilder('cb')
                ->select('COUNT(cb)')
                ->where('cb.beneficiaire = :beneficiary')
                ->andWhere('cb.membre = :member')
                ->setParameters([
                    'beneficiary' => $beneficiary,
                    'member' => $user->getSubjectMembre(),
                ])
                ->getQuery();
            try {
                $result = $query->getSingleScalarResult();
            } catch (\Exception) {
                $result = count($query->getResult());
            }

            return 0 === $result;
        }

        return false;
    }
}
