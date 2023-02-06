<?php

namespace App\Validator\Constraints\Evenement;

use App\Entity\Evenement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidator extends ConstraintValidator
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @param Evenement $entity
     *
     * @throws \Exception
     */
    public function validate($entity, Constraint $constraint)
    {
//        throw new \Exception(__METHOD__);
        if (null === $entity || '' === $entity) {
            return;
        }

        if (!$entity->getDate() instanceof \DateTime) {
            $entity->setDate(new \DateTime($entity->getDate()));
        }

        if ((null === $entity->getId()) && $entity->getDate() < (new \DateTime())) {
            $this->context->buildViolation($constraint->messageRappelBeforeNow)
                ->atPath('date')
                ->addViolation();

            return;
        }

        $currentDate = $entity->getDate();

        $originalRappel = $this->entityManager
            ->getUnitOfWork()
            ->getOriginalEntityData($entity);

        if (null !== $originalRappel && !empty($originalRappel['date']) && $originalRappel['date'] !== $currentDate && $originalRappel['date'] < (new \DateTime())) {
            $this->context->buildViolation($constraint->messageRappelBeforeNow)
                ->atPath('date')
                ->addViolation();

            return;
        }

        foreach ($entity->getRappels() as $rappel) {
            if ($rappel->getDate() > $entity->getDate()) {
                $this->context->addViolation($constraint->messageRappelAfterDateEvent);

                return;
            }
        }
    }
}
