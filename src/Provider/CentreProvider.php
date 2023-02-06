<?php

namespace App\Provider;

use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\Gestionnaire;
use App\Entity\Membre;
use App\Entity\MembreCentre;
use App\Entity\Subject;
use App\Entity\User;
use App\Entity\UserWithCentresInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CentreProvider
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ApiClientManager $apiClientManager,
    ) {
    }

    /**
     * @return Centre[]|Collection
     */
    public function getEntitiesForUser(?User $user)
    {
        if ($user->isMembre()) {
            return $user->getSubjectMembre()->getCentres();
        }

        if ($user->isGestionnaire()) {
            return $user->getSubjectGestionnaire()->getCentres();
        }

        if ($user->isBeneficiaire()) {
            return $user->getSubjectBeneficiaire()->getCentres();
        }

        return [];
    }

    public function getAllCentresWithAddress()
    {
        return $this->em->createQueryBuilder()
            ->select('c', 'a')
            ->from('App:Centre', 'c')
            ->innerJoin('c.adresse', 'a')
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }

    /**
     * @return array
     */
    public function getBeneficiairesFromGestionnaire(Gestionnaire $gestionnaire)
    {
        return $this->em->createQueryBuilder()
            ->select('b', 'bc', 'c', 'g', 'u')
            ->from('App:Beneficiaire', 'b')
            ->innerJoin('b.beneficiairesCentres', 'bc')
            ->innerJoin('bc.centre', 'c')
            ->innerJoin('c.gestionnaire', 'g')
            ->innerJoin('b.user', 'u')
            ->where('g.id = '.$gestionnaire->getId())
            ->andWhere('b.isCreating = FALSE')
            ->andWhere('bc.bValid = TRUE')
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }

    /**
     * @return array
     */
    public function getMembresFromGestionnaire(Gestionnaire $gestionnaire)
    {
        return $this->em->createQueryBuilder()
            ->select('m', 'mc', 'c', 'g', 'u')
            ->from('App:Membre', 'm')
            ->innerJoin('m.membresCentres', 'mc')
            ->innerJoin('mc.centre', 'c')
            ->innerJoin('c.gestionnaire', 'g')
            ->innerJoin('m.user', 'u')
            ->where('g.id = '.$gestionnaire->getId())
            ->andWhere('mc.bValid = TRUE')
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }

    /**
     * @return array
     */
    public function getBeneficiairesFromMembreByCentre(Membre $membre, $takeUnvalid = false)
    {
        /** @var Beneficiaire $beneficiaire */
        $beneficiaires = $this->getBeneficiairesFromMembre($membre, $takeUnvalid);

        $arRet = [];
        foreach ($membre->getMembresCentres() as $membreCentre) {
            $centre = $membreCentre->getCentre();
            $arRet[$centre->getId()] = ['centre' => $centre, 'beneficiaires' => []];
        }
        foreach ($beneficiaires as $beneficiaire) {
            foreach ($beneficiaire->getBeneficiairesCentres() as $beneficiaireCentre) {
                if (array_key_exists($beneficiaireCentre->getCentre()->getId(), $arRet)) {
                    $arRet[$beneficiaireCentre->getCentre()->getId()]['beneficiaires'][] = $beneficiaire;
                }
            }
        }

        return $arRet;
    }

    /**
     * @return Beneficiaire[]
     */
    public function getBeneficiairesFromMembre(Membre $membre, ?bool $takeUnvalid = false)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('b', 'bc', 'c', 'mc', 'm', 'u')
            ->from(Beneficiaire::class, 'b')
            ->innerJoin('b.beneficiairesCentres', 'bc')
            ->innerJoin('bc.centre', 'c')
            ->innerJoin('c.membresCentres', 'mc')
            ->innerJoin('mc.membre', 'm')
            ->innerJoin('b.user', 'u')
            ->where('m.id = '.$membre->getId())
            ->andWhere("mc.droits LIKE '%".MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES."\";b:1%'")
            ->andWhere('b.isCreating = FALSE');

        if (!$takeUnvalid) {
            $qb->andWhere('bc.bValid = TRUE');
        }

        $results = $qb
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        usort($results, static function (Beneficiaire $a, Beneficiaire $b) {
            $aStr = strtolower(sprintf('%s %s', $a->getUser()->getPrenom(), $a->getUser()->getNom()));
            $bStr = strtolower(sprintf('%s %s', $b->getUser()->getPrenom(), $b->getUser()->getNom()));

            return $aStr <=> $bStr;
        });

        return $results;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getScalarBeneficaireForMembre($id, $membreId)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('count(b.id)')
            ->from(Beneficiaire::class, 'b')
            ->innerJoin('b.beneficiairesCentres', 'bc')
            ->innerJoin('bc.centre', 'c')
            ->innerJoin('c.membresCentres', 'mc')
            ->innerJoin('mc.membre', 'm')
            ->where('m.id = '.$membreId)
            ->andWhere("mc.droits LIKE '%".MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES."\";b:1%'")
            ->andWhere('b.id = '.$id)
            ->andWhere('b.isCreating = FALSE');

        return $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Centre[]
     */
    public function getCentresFromUserWithCentre(Subject $subject): array
    {
        if ($subject instanceof Gestionnaire) {
            return $this->getCentresFromGestionnaire($subject);
        } elseif ($subject instanceof Membre) {
            return $this->em->createQueryBuilder()
                ->select('c', 'a', 'bc', 'b', 'u')
                ->from('App:Centre', 'c')
                ->leftJoin('c.adresse', 'a')
                ->leftJoin('c.membresCentres', 'mc')
                ->leftJoin('c.beneficiairesCentres', 'bc')
                ->leftJoin('bc.beneficiaire', 'b')
                ->leftJoin('b.user', 'u')
                ->leftJoin('mc.membre', 'm')
                ->where('m.id = '.$subject->getId())
                ->getQuery()
                ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
                ->getResult();
        } elseif ($subject instanceof Beneficiaire) {
            return $this->em->createQueryBuilder()
                ->select('c', 'a')
                ->from('App:Centre', 'c')
                ->leftJoin('c.adresse', 'a')
                ->innerJoin('c.beneficiairesCentres', 'bc')
                ->innerJoin('bc.beneficiaire', 'b')
                ->where('b.id = '.$subject->getId())
                ->getQuery()
                ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
                ->getResult();
        }

        return [];
    }

    /**
     * @return Centre[]
     */
    public function getPendingCentresFromUserWithCentre(UserWithCentresInterface $subject): array
    {
        if ($subject->isMembre()) {
            return $this->em->getRepository(Centre::class)->createQueryBuilder('c')
                ->innerJoin('c.membresCentres', 'mc')
                ->innerJoin('mc.membre', 'm')
                ->where('m.id = '.$subject->getId())
                ->andWhere('mc.bValid = FALSE')
                ->getQuery()
                ->getResult();
        }

        if ($subject->isBeneficiaire()) {
            $query = $this->em->createQuery(
                'SELECT c, a
                FROM App\Entity\Centre c
                INNER JOIN c.beneficiairesCentres bc
                INNER JOIN bc.beneficiaire b
                LEFT JOIN c.adresse a
                WHERE b.id = :id
                AND bc.bValid = FALSE'
            )->setParameter('id', $subject->getId());

            return $query->getResult();
        }

        return [];
    }

    /**
     * @return array
     */
    public function getOtherMembresFromMembreByCentre(Membre $membre)
    {
        $otherMembres = $this->getOtherMembresFromMembre($membre);

        $arRet = [];
        foreach ($membre->getMembresCentres() as $membreCentre) {
            $centre = $membreCentre->getCentre();
            $arRet[$centre->getId()] = ['centre' => $centre, 'otherMembres' => []];
        }
        foreach ($otherMembres as $otherMembre) {
            if ($otherMembre->getId() === $membre->getId()) {
                continue;
            }
            foreach ($otherMembre->getMembresCentres() as $otherMembreCentre) {
                $arRet[$otherMembreCentre->getCentre()->getId()]['otherMembres'][] = $otherMembre;
            }
        }

        return $arRet;
    }

    /**
     * @return array
     */
    public function getOtherMembresFromMembre(Membre $membre)
    {
        $results = $this->em->createQueryBuilder()
            ->select('m', 'mc', 'c', 'omc', 'om', 'u')
            ->from('App:Membre', 'om')
            ->leftJoin('om.user', 'u')
            ->leftJoin('om.membresCentres', 'omc')
            ->leftJoin('omc.centre', 'c')
            ->leftJoin('c.membresCentres', 'mc')
            ->leftJoin('mc.membre', 'm')
            ->where('m.id = '.$membre->getId())
            ->andWhere("mc.droits LIKE '%".MembreCentre::TYPEDROIT_GESTION_MEMBRES."\";b:1%'")
            ->andWhere('omc.bValid = TRUE')
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        // On enlève le membre concerné
        $results2 = [];
        foreach ($results as $result) {
            if ($result->getId() !== $membre->getId()) {
                $results2[] = $result;
            }
        }

        return $results2;
    }

    /**
     * @return Centre[]
     */
    public function getCentresFromGestionnaire(Gestionnaire $gestionnaire): array
    {
        return $this->em->createQueryBuilder()
            ->select('c', 'mc')
            ->from('App:Centre', 'c')
            ->leftJoin('c.membresCentres', 'mc')
            ->where('c.gestionnaire = '.$gestionnaire->getId())
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function getCentresForAdmin()
    {
        return $this->em->createQueryBuilder()
            ->select('c', 'mc', 'bc', 'sms')
            ->from('App:Centre', 'c')
            ->leftJoin('c.membresCentres', 'mc')
            ->leftJoin('c.beneficiairesCentres', 'bc')
            ->leftJoin('c.sms', 'sms')
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }

    public function getEntity($id): Centre
    {
        /** @var Centre $entity */
        if (!$entity = $this->em->find(Centre::class, $id)) {
            throw new NotFoundHttpException('No center found for id '.$id);
        }

        return $entity;
    }

    public function getEntityByDistantId($distantId): Centre
    {
        $client = $this->apiClientManager->getCurrentOldClient();
        /** @var Centre $entity */
        if (!$entity = $this->em->getRepository(Centre::class)->findByDistantId($distantId, $client->getRandomId())) {
            throw new NotFoundHttpException('No center found for distant id '.$distantId);
        }

        return $entity;
    }
}
