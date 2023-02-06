<?php

namespace App\Validator\Constraints;

use App\Entity\ClientBeneficiaire;
use App\Entity\ClientEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueExternalLinkValidator extends ConstraintValidator
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param ClientEntity[]|ArrayCollection $value The value that should be validated
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueExternalLink) {
            throw new UnexpectedTypeException($constraint, UniqueExternalLink::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        /**
         * Vérification si le lien externe existe déjà
         * en base de données ou pour ce bénéficiaire.
         */
        $externalLinkAlreadyLinks = new ArrayCollection();
        $externalLinks = new ArrayCollection();
        /* @var ClientBeneficiaire[] $externalLinks */
        foreach ($value as $entity) {
            if (null === $entity->getClient()) {
                $this->context->addViolation($constraint->messageMissing);

                return;
            }

            $externalLink = $this->entityManager->getRepository(ClientEntity::class)->findOneBy([
                'client' => $entity->getClient(),
                'distantId' => $entity->getDistantId(),
                'entity_name' => $entity->getEntityName(),
            ]);

            if (null !== $externalLink) {
                $externalLinks = $value->filter(static function ($element) use ($externalLink) {
                    return $element->getClient() === $externalLink->getClient() &&
                        $element->getDistantId() === $externalLink->getDistantId() &&
                        $element->getEntity() === $externalLink->getEntity();
                });
            }

            if (null !== $externalLink && !$externalLinkAlreadyLinks->contains($externalLink) && (!$externalLinks->first() || $externalLinks->count() > 1)) {
                $this->context->buildViolation($constraint->messageDuplicate)
                    ->setParameter('{{ string }}', (string) $externalLink)
                    ->atPath('external_link')
                    ->addViolation();
                $externalLinkAlreadyLinks->add($externalLink);
            }
        }
    }
}
