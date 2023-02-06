<?php

namespace App\Provider;

use App\Entity\Client;
use App\Entity\DonneePersonnelle;
use App\Entity\Evenement;
use App\Entity\Rappel;
use App\Event\DonneePersonnelleEvent;
use App\Event\REEvent;
use App\Form\Type\EvenementSimpleType;
use App\Form\Type\EvenementType;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
use Doctrine\ORM\Query;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class EvenementProvider extends DonneePersonnelleProvider
{
    protected string $entityName = Evenement::class;
    protected string $formType = EvenementType::class;
    protected string $formSimpleType = EvenementSimpleType::class;

    public function getDueEvents()
    {
        $now = new \DateTime();
        $nowLess12h05 = new \DateTime(date('Y-m-d H:i:s', strtotime($now->format('Y-m-d H:i:s').'-12 hours -5 minutes')));
        $nowPlus12h = new \DateTime(date('Y-m-d H:i:s', strtotime($now->format('Y-m-d H:i:s').'+14 hours')));

        return $this->em->createQueryBuilder()
            ->select('rappel', 'evenement', 'beneficiaire', 'user')
            ->from(Rappel::class, 'rappel')
            ->innerJoin('rappel.evenement', 'evenement')
            ->innerJoin('evenement.beneficiaire', 'beneficiaire')
            ->innerJoin('beneficiaire.user', 'user')
            ->where('evenement.date > :nowLess12h05')
            ->andWhere('rappel.bEnvoye = false')
            ->andWhere('rappel.date > :nowLess12h05')
            ->andWhere('rappel.date < :nowPlus12h')
            ->setParameters([
                'nowPlus12h' => $nowPlus12h,
                'nowLess12h05' => $nowLess12h05,
            ])
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }

    public function delete(DonneePersonnelle $entity, bool $log = true): void
    {
        if (false === $this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_DELETE, $entity)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantDelete'));
        }

        if ($log) {
            $this->eventDispatcher->dispatch(
                new DonneePersonnelleEvent($entity, $entity->getBeneficiaire()->getUser(), DonneePersonnelleEvent::DONNEEPERSONNELLE_DELETED),
                REEvent::RE_EVENT_DONNEEPERSONNELLE,
            );
        }

        if (null !== $entity->getSms()) {
            $entity->setArchive(true);
        } else {
            $this->em->remove($entity);
        }
        $this->em->flush();
    }

    /**
     * @param string|null $accessAttribute
     */
    public function getEntity($id, $accessAttribute = null): Evenement
    {
        /** @var Evenement $entity */
        if (!$entity = $this->em->find(Evenement::class, $id)) {
            throw new NotFoundHttpException('No evenement found for id '.$id);
        }

        $attributes = [DonneePersonnelleVoter::DONNEEPERSONNELLE_VIEW, $accessAttribute];

        foreach ($attributes as $attribute) {
            if ($attribute && !$this->authorizationChecker->isGranted($attribute, $entity)) {
                throw new AccessDeniedException();
            }
        }

        return $entity;
    }

    /**
     * @param Evenement $entity
     *
     * @throws \Exception
     */
    public function populate($entity, Client $client): void
    {
        parent::populate($entity, $client);

        $entity
            ->setCommentaire($this->request->get('commentaire'))
            ->setLieu($this->request->get('lieu'))
            ->setDate(new \DateTime($this->request->get('date')));

        $rappels = $this->request->get('rappels');

        $originalRappels = [];

        foreach ($entity->getRappels() as $rappel) {
            if (!$rappel->getArchive()) {
                $originalRappels[] = $rappel;
            }
        }

        if (0 !== count($originalRappels)) {
            foreach ($originalRappels as $originalRappel) {
                $inArray = false;
                if (null !== $rappels) {
                    foreach ($rappels as $rappel) {
                        if (isset($rappel['id']) && $originalRappel->getId() === $rappel['id']) {
                            $inArray = true;
                            break;
                        }
                    }
                }

                if (!$inArray) {
                    if (null !== $originalRappel->getSms()) {
                        $originalRappel->setArchive(true);
                        $this->em->persist($originalRappel);
                    } else {
                        $entity->removeRappel($originalRappel);
                        $this->em->remove($originalRappel);
                    }
                }
            }
        }

        if (null !== $rappels) {
            foreach ($rappels as $rappel) {
                if (empty($rappel['id'])) {
                    $rappelObject = new Rappel();
                    $rappelDate = is_array($rappel) ? $rappel['date'] : $rappel;
                    $rappelObject->setDate(new \DateTime($rappelDate));
                    $entity->addRappel($rappelObject);
                }
            }
        }
    }
}
