<?php

namespace App\Validator\Constraints\Beneficiaire;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidator extends ConstraintValidator
{
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param Beneficiaire $entity
     *
     * @throws \Exception
     */
    public function validate($entity, Constraint $constraint)
    {
        if (null === $entity || '' === $entity) {
            return;
        }

        $dateNaissance = $entity->getDateNaissance();
        if (!$dateNaissance instanceof \DateTime) {
            $errors = $this->validator->validate($dateNaissance, new Assert\Date());
            if (0 !== count($errors)) {
                $this->context->buildViolation($constraint->messageDateNaissance, ['%string%' => $dateNaissance->format('Y-m-d')])->atPath('dateNaissance')->addViolation();

                return;
            }
            if (null !== $dateNaissance) {
                $entity->setDateNaissance(new \DateTime($dateNaissance));
            }
        }

        $errorUniqueClient = new ArrayCollection();
        /** @var Centre[] $errorBeneficiairesCentres */
        $errorBeneficiairesCentres = new ArrayCollection();
        $beneficiairesCentres = $entity->getBeneficiairesCentres();
        foreach ($beneficiairesCentres as $a) {
            $countBeneficiairesCentres = $beneficiairesCentres->filter(static function ($b) use ($a) {
                return $a->getCentre() === $b->getCentre();
            })->count();
            if (($countBeneficiairesCentres > 1) && !$errorBeneficiairesCentres->contains($a->getCentre())) {
                $errorBeneficiairesCentres->add($a->getCentre());
            }

            $countClient = $beneficiairesCentres->filter(static function ($b) use ($a) {
                return null !== $a->getExternalLink() && $a->getExternalLink() === $b->getExternalLink();
            })->count();
            if (($countClient > 1) && !$errorUniqueClient->contains($a->getExternalLink())) {
                $errorUniqueClient->add($a->getExternalLink());
            }
        }

        foreach ($errorBeneficiairesCentres as $item) {
            $this->context->buildViolation($constraint->messageDuplicateBeneficiaireCentre)
                ->setParameter('{{ string }}', $item->getNom())
                ->atPath('center')
                ->addViolation();
        }
        foreach ($errorUniqueClient as $item) {
            $this->context->buildViolation($constraint->messageDuplicateCentreBeneficaireExternalLink)
                ->setParameter('{{ string }}', (string) $item)
                ->atPath('external_link')
                ->addViolation();
        }
    }
}
