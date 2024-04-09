<?php

namespace App\Validator\Constraints\Rappel;

use App\Entity\Rappel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntityValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param Rappel $reminder
     *
     * @throws \Exception
     */
    public function validate(mixed $reminder, Constraint $constraint): void
    {
        if (!$reminder instanceof Rappel) {
            throw new UnexpectedTypeException($reminder, Rappel::class);
        }

        $currentDate = $reminder->getDate();

        if (!$currentDate instanceof \DateTime) {
            $reminder->setDate(new \DateTime($currentDate));
        }

        $this->checkBeneficiaryHasPhoneNumber($reminder);
        $this->preventUpdateAlreadySendReminder($reminder, $constraint);
        $this->checkNewReminderDatetime($reminder, $constraint);
    }

    private function checkBeneficiaryHasPhoneNumber(Rappel $reminder): void
    {
        $beneficiary = $reminder->getEvenement()->getBeneficiaire();

        if (null === $reminder->getId() && !$beneficiary?->getUser()?->getTelephone()) {
            $this->context->addViolation('Pas de numéro de téléphone enregistré.');
        }
    }

    private function preventUpdateAlreadySendReminder(Rappel $reminder, Constraint $constraint): void
    {
        if ($reminder->getId()) {
            $reminderBeforeUpdate = $this->entityManager
                ->getUnitOfWork()
                ->getOriginalEntityData($reminder);
            $dateBeforeUpdate = $reminderBeforeUpdate['date'];
            $dateBeforeUpdateToUtc = new \DateTime($dateBeforeUpdate->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));

            if ($reminder->getBEnvoye() && $reminder->getDateToUtcTimezone() !== $dateBeforeUpdateToUtc) {
                $this->context->buildViolation($constraint->messageSMSAlreadySend)
                    ->setParameter('{{ string }}', $dateBeforeUpdate->format('d/m/Y à H:i'))
                    ->addViolation();
            }
        }
    }

    private function checkNewReminderDatetime(Rappel $reminder, Constraint $constraint): void
    {
        $nowMinus12HoursUtc = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-12 hours -5 minutes');

        if (null === $reminder->getId() && $reminder->getDateToUtcTimezone() < $nowMinus12HoursUtc) {
            $this->context->addViolation($constraint->messageRappelBeforeNow);
        }
    }
}
