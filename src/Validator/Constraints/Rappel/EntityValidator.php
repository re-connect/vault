<?php

namespace App\Validator\Constraints\Rappel;

use App\Entity\Rappel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidator extends ConstraintValidator
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var ValidatorInterface */
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @param Rappel $entity
     *
     * @throws \Exception
     */
    public function validate($entity, Constraint $constraint)
    {
        if (null === $entity || '' === $entity) {
            return;
        }
        $currentDate = $entity->getDate();

        if (!$currentDate instanceof \DateTime) {
            $entity->setDate(new \DateTime($currentDate));
        }

        $beneficiary = $entity->getEvenement()->getBeneficiaire();

        if (null === $entity->getId() && $beneficiary && !$beneficiary->getUser()->getTelephone()) {
            $this->context->addViolation('Pas de numéro de téléphone enregistré.');

            return;
        }

        if (null === $entity->getId() && $currentDate < new \DateTime()) {
            $this->context->addViolation($constraint->messageRappelBeforeNow);

            return;
        }

        $originalRappel = $this->entityManager
            ->getUnitOfWork()
            ->getOriginalEntityData($entity);

        $originalRappelExists = null !== $originalRappel && !empty($originalRappel['id']);

        if ($originalRappelExists && $originalRappel['date'] !== $currentDate && null !== $entity->getSms()) {
            $this->context->buildViolation($constraint->messageSMSAlreadySend)
                ->setParameter('{{ string }}', $originalRappel['date']->format('d/m/Y à H:i'))
                ->addViolation();

            return;
        }

        if ($originalRappelExists && !$originalRappel['bEnvoye'] && $originalRappel['date'] !== $currentDate && $originalRappel['date'] < (new \DateTime())) {
            $this->context->addViolation($constraint->messageRappelBeforeNow);

            return;
        }
    }
}
