<?php

namespace App\Listener;

use App\Entity\FaqQuestion;
use App\Repository\FaqQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class FaqQuestionListener
{
    private FaqQuestionRepository $repository;
    private EntityManagerInterface $em;
    protected array $faqQuestionToPersist;

    public function __construct(FaqQuestionRepository $repository, EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->em = $em;
        $this->faqQuestionToPersist = [];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof FaqQuestion) {
            return;
        }
        $position = $entity->getPosition();
        $faqQuestionsWithHigherOrEqualPosition = $this->repository->findGreaterThanOrEqual($position);
        if (null === $position) {
            $highestPosition = $this->repository->findOneBy([], ['position' => 'DESC'])->getPosition();
            $entity->setPosition($highestPosition + 1);
        } elseif (0 < count($faqQuestionsWithHigherOrEqualPosition)) {
            foreach ($faqQuestionsWithHigherOrEqualPosition as $faqQuestion) {
                $faqQuestionPosition = $faqQuestion->getPosition();
                $faqQuestion->setPosition($faqQuestionPosition + 1);
                $this->em->persist($faqQuestion);
            }
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof FaqQuestion) {
            return;
        }
        $this->faqQuestionToPersist[] = $entity;
        $position = $entity->getPosition();
        $faqQuestionsWithHigherOrEqualPosition = $this->repository->findGreaterThanOrEqual($position);
        $faqQuestionsWithHigherOrEqualPosition = array_values(array_filter($faqQuestionsWithHigherOrEqualPosition, function ($faq) use ($entity) {
            return $faq->getId() !== $entity->getId();
        }));
        if (null === $position) {
            $highestPosition = $this->repository->findOneBy([], ['position' => 'DESC'])->getPosition();
            $entity->setPosition($highestPosition + 1);
        } elseif (0 < count($faqQuestionsWithHigherOrEqualPosition) && $position === $faqQuestionsWithHigherOrEqualPosition[0]->getPosition()) {
            foreach ($faqQuestionsWithHigherOrEqualPosition as $faqQuestion) {
                $faqQuestionPosition = $faqQuestion->getPosition();
                $faqQuestion->setPosition($faqQuestionPosition + 1);
                $this->faqQuestionToPersist[] = $faqQuestion;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event)
    {
        if (!empty($this->faqQuestionToPersist)) {
            $em = $event->getEntityManager();
            foreach ($this->faqQuestionToPersist as $thing) {
                $em->persist($thing);
            }
            $this->faqQuestionToPersist = [];
            $em->flush();
        }
    }
}
