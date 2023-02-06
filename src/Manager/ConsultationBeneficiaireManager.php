<?php

namespace App\Manager;

use App\Entity\Beneficiaire;
use App\Entity\ConsultationBeneficiaire;
use App\Entity\Membre;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ConsultationBeneficiaireManager
{
    private EntityManagerInterface $em;
    private TokenStorageInterface $tokenStorage;
    private RequestStack $requestStack;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
    }

    /**
     * Enregistrement de la première consultation d'un bénéficiaire par un membre.
     */
    public function handleUserVisit(Beneficiaire $beneficiaire): bool
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        if (is_object($user) && $user->isMembre() && $this->isFirstVisit($beneficiaire, $user->getSubjectMembre())) {
            $consultationBeneficiaire = new ConsultationBeneficiaire();

            $consultationBeneficiaire
                ->setBeneficiaire($beneficiaire)
                ->setMembre($user->getSubjectMembre());

            $this->em->persist($consultationBeneficiaire);

            $this->requestStack->getSession()->set('firstConsultationBeneficiaire', $beneficiaire->getId());

            $this->em->flush();

            return true;
        }

        return false;
    }

    private function isFirstVisit(Beneficiaire $beneficiaire, Membre $membre): bool
    {
        $result = $this->em->createQueryBuilder()
            ->select('cb')
            ->from('App:ConsultationBeneficiaire', 'cb')
            ->where('cb.beneficiaire = '.$beneficiaire->getId())
            ->andWhere('cb.membre = '.$membre->getId())
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        return null === $result || 0 === count($result);
    }
}
