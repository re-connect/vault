<?php

namespace App\ManagerV2;

use App\Entity\Beneficiaire;
use App\Entity\ConsultationBeneficiaire;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class MemberBeneficiaryManager
{
    use UserAwareTrait;
    private EntityManagerInterface $em;
    private Security $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
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
            } catch (\Exception $e) {
                $result = count($query->getResult());
            }

            return 0 === $result;
        }

        return false;
    }
}
