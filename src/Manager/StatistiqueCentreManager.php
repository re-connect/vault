<?php

namespace App\Manager;

use App\Entity\Centre;
use App\Entity\StatistiqueCentre;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

class StatistiqueCentreManager
{
    /**
     * Constructor.
     */
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @throws \Exception
     */
    public function storeStatistics(): void
    {
        $iterableResult = $this->em->createQueryBuilder()
            ->select('c', 'b', 'bc', 'mc', 'm', 'sc', 'sms')
            ->from(Centre::class, 'c')
            ->leftJoin('c.beneficiairesCentres', 'bc')
            ->leftJoin('bc.beneficiaire', 'b')
            ->leftJoin('c.membresCentres', 'mc')
            ->leftJoin('mc.membre', 'm')
            ->leftJoin('c.statistiquesCentre', 'sc')
            ->leftJoin('c.sms', 'sms')
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
        //            ->iterate();

        $arStatistics = [
            StatistiqueCentre::STATISTIQUECENTRE_NB_BENEFICIAIRES,
            StatistiqueCentre::STATISTIQUECENTRE_NB_MEMBRES,
            StatistiqueCentre::STATISTIQUECENTRE_SMS_ENVOYES,
        ];

        //        $i = 1;
        //
        //        /** @var Centre $entity */
        //        foreach ($iterableResult as $row) {
        //            $entity = $row[0];
        //            dump($entity);die;
        //
        // //            foreach ($arStatistics as $statKey) {
        // //                $this->updateStatisticValue($centre, $statKey);
        // //            }
        //
        //            if (($i % 20) === 0) {
        //                $output->writeln('enregistrement en cours !!!!!!!!!');
        // //                $this->em->flush(); // Executes all updates.
        // //                $this->em->clear(); // Detaches all objects from Doctrine!
        //            }
        //            $i++;
        //            $output->writeln($i);
        //        }
        //        $this->em->flush();
        foreach ($iterableResult as $centre) {
            foreach ($arStatistics as $statKey) {
                $this->updateStatisticValue($centre, $statKey);
            }
        }
        $this->em->flush();
    }

    /**
     * @throws \Exception
     */
    private function updateStatisticValue(Centre $centre, string $statKey): void
    {
        $foundStat = null;
        foreach ($centre->getStatistiquesCentre() as $statistique) {
            if ($statistique->getNom() == $statKey) {
                $foundStat = $statistique;
            }
        }
        if (null == $foundStat) {
            $foundStat = new StatistiqueCentre();
            $foundStat->setCentre($centre);
            $foundStat->setNom($statKey);
            $foundStat->setDonnees([]);
        }

        $dateStr = (new \DateTime())->format('d/m/Y');
        $donnees = $foundStat->getDonnees();
        $donnees[$dateStr] = $this->getStatisticValue($centre, $statKey);
        $foundStat->setDonnees($donnees);
        $this->em->persist($foundStat);
    }

    private function getStatisticValue(Centre $centre, string $statKey): ?int
    {
        return match ($statKey) {
            StatistiqueCentre::STATISTIQUECENTRE_NB_BENEFICIAIRES => count($centre->getBeneficiairesCentres()),
            StatistiqueCentre::STATISTIQUECENTRE_NB_MEMBRES => count($centre->getMembresCentres()),
            StatistiqueCentre::STATISTIQUECENTRE_SMS_ENVOYES => count($centre->getSMS()),
            default => null,
        };
    }
}
