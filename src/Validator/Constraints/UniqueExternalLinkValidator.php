<?php

namespace App\Validator\Constraints;

use App\Api\Dto\BeneficiaryDto;
use App\Api\Manager\ApiClientManager;
use App\Entity\ClientBeneficiaire;
use App\Repository\BeneficiaireRepository;
use App\Repository\ClientEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueExternalLinkValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ClientEntityRepository $clientEntityRepository,
        private readonly BeneficiaireRepository $beneficiaireRepository,
        private readonly ApiClientManager $apiClientManager,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueExternalLink) {
            throw new UnexpectedTypeException($constraint, UniqueExternalLink::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if ($value instanceof BeneficiaryDto) {
            if ($this->beneficiaireRepository->findByDistantId($value->distantId, $this->apiClientManager->getCurrentOldClient()->getRandomId())) {
                $this->context->buildViolation($constraint->messageDistantIdDuplicate)
                    ->setParameter('{{ string }}', (string) $value->distantId)
                    ->atPath('external_link')
                    ->addViolation();
            }

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

            $externalLink = $this->clientEntityRepository->findOneBy([
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
