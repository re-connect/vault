<?php

namespace App\Listener;

use App\Entity\Attributes\FaqQuestion;
use App\Repository\FaqQuestionRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: FaqQuestion::class)]
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: FaqQuestion::class)]
#[AsEntityListener(event: Events::postFlush, method: 'postFlush', entity: FaqQuestion::class)]
class FaqQuestionListener
{
    /**
     * @param FaqQuestion[] $faqQuestionToPersist
     */
    public function __construct(private readonly FaqQuestionRepository $repository, private array $faqQuestionToPersist = [])
    {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function prePersist(FaqQuestion $entity, LifecycleEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $position = $entity->getPosition();
        $faqQuestionsWithHigherOrEqualPosition = $this->repository->findGreaterThanOrEqual($position);
        if (null === $position) {
            $highestPosition = $this->repository->findOneBy([], ['position' => 'DESC'])->getPosition();
            $entity->setPosition($highestPosition + 1);
        } elseif (0 < count($faqQuestionsWithHigherOrEqualPosition)) {
            foreach ($faqQuestionsWithHigherOrEqualPosition as $faqQuestion) {
                $faqQuestionPosition = $faqQuestion->getPosition();
                $faqQuestion->setPosition($faqQuestionPosition + 1);
                $em->persist($faqQuestion);
            }
        }
    }

    public function preUpdate(FaqQuestion $entity, PreUpdateEventArgs $args): void
    {
        $this->faqQuestionToPersist[] = $entity;
        $position = $entity->getPosition();
        $faqQuestionsWithHigherOrEqualPosition = $this->repository->findGreaterThanOrEqual($position);
        $faqQuestionsWithHigherOrEqualPosition = array_values(array_filter($faqQuestionsWithHigherOrEqualPosition, fn ($faq) => $faq->getId() !== $entity->getId()));
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

    public function postFlush(FaqQuestion $entity, PostFlushEventArgs $event): void
    {
        if (!empty($this->faqQuestionToPersist)) {
            $em = $event->getObjectManager();
            foreach ($this->faqQuestionToPersist as $thing) {
                $em->persist($thing);
            }
            $this->faqQuestionToPersist = [];
            $em->flush();
        }
    }
}
